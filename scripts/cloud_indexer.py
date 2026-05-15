import os
import sys
import json
import time
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build
from googleapiclient.http import MediaIoBaseDownload
import google.generativeai as genai
from dotenv import load_dotenv

# Path library milik LENOVO (Sesuaikan jika sudah di VPS)
sys.path.append(r"C:\Users\LENOVO\AppData\Roaming\Python\Python314\site-packages")

load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '../.env'))
api_key = os.getenv('GEMINI_API_KEY')
model_name = os.getenv('GEMINI_MODEL', 'gemini-2.5-flash')
genai.configure(api_key=api_key)

if len(sys.argv) < 4:
    print(json.dumps({"error": "Argumen tidak lengkap."}))
    sys.exit(1)

folder_id, user_prompt, access_token = sys.argv[1], sys.argv[2], sys.argv[3]

try:
    creds = Credentials(token=access_token)
    drive_service = build('drive', 'v3', credentials=creds)
except Exception as e:
    print(json.dumps({"error": f"Gagal Drive: {str(e)}"}))
    sys.exit(1)

def download_temp(file_id, file_name):
    temp_dir = os.path.join(os.path.dirname(__file__), '../storage/app/temp_videos')
    if not os.path.exists(temp_dir): os.makedirs(temp_dir)
    file_path = os.path.join(temp_dir, file_name)
    request = drive_service.files().get_media(fileId=file_id)
    with open(file_path, 'wb') as fh:
        downloader = MediaIoBaseDownload(fh, request)
        done = False
        while not done: _, done = downloader.next_chunk()
    return file_path

def analisa_video(file_path, prompt):
    video_file = genai.upload_file(path=file_path)
    while video_file.state.name == "PROCESSING":
        time.sleep(5)
        video_file = genai.get_file(video_file.name)
    
    model = genai.GenerativeModel(model_name)
    instruksi = f"Instruksi: {prompt}. Jawab HANYA JSON murni: {{\"data\": [{{\"timestamp\": \"menit:detik\", \"description\": \"...\"}}]}}"
    response = model.generate_content([video_file, instruksi])
    genai.delete_file(video_file.name)
    
    try:
        raw_json = response.text.replace('```json', '').replace('```', '').strip()
        return json.loads(raw_json)
    except: return {"data": []}

try:
    query = f"'{folder_id}' in parents and mimeType='video/mp4' and trashed=false"
    results = drive_service.files().list(q=query, fields="files(id, name)").execute()
    items = results.get('files', [])

    semua_hasil = []
    for item in items:
        # Download hanya untuk analisis, lalu hapus
        path = download_temp(item['id'], item['name'])
        hasil = analisa_video(path, user_prompt)
        
        if "data" in hasil:
            semua_hasil.append({
                "filename": item['name'],
                "file_id": item['id'], # PENTING: Untuk streaming cloud
                "matches": hasil["data"]
            })
        if os.path.exists(path): os.remove(path) # Hapus agar server tidak penuh

    print(json.dumps({"status": "success", "results": semua_hasil}))
except Exception as e:
    print(json.dumps({"error": str(e)}))