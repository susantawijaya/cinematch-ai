import os
import sys
import json
import time
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build
from googleapiclient.http import MediaIoBaseDownload
import google.generativeai as genai
from dotenv import load_dotenv

# 🔥 KUNCI ANTI-TERDIAM: Paksa Windows menggunakan UTF-8 dan melepaskan buffer
sys.stdout.reconfigure(encoding='utf-8')

# Path library milik LENOVO (Sesuaikan jika sudah di-deploy ke server)
sys.path.append(r"C:\Users\LENOVO\AppData\Roaming\Python\Python314\site-packages")

load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '../.env'))
api_key = os.getenv('GEMINI_API_KEY')
model_name = os.getenv('GEMINI_MODEL', 'gemini-2.5-flash')
genai.configure(api_key=api_key)

if len(sys.argv) < 4:
    print(json.dumps({"error": "Argumen tidak lengkap."}), flush=True)
    sys.exit(1)

folder_id, user_prompt, access_token = sys.argv[1], sys.argv[2], sys.argv[3]

try:
    creds = Credentials(token=access_token)
    drive_service = build('drive', 'v3', credentials=creds)
except Exception as e:
    print(json.dumps({"error": f"Gagal API Drive: {str(e)}"}), flush=True)
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

def parse_time_to_seconds(timestamp_str):
    try:
        m, s = timestamp_str.split(':')
        return int(m) * 60 + int(s)
    except:
        return 0

def analisa_video(file_path, prompt):
    video_file = genai.upload_file(path=file_path)
    while video_file.state.name == "PROCESSING":
        time.sleep(5)
        video_file = genai.get_file(video_file.name)
    
    model = genai.GenerativeModel(
        model_name,
        generation_config={
            "temperature": 0.0,
            "response_mime_type": "application/json"
        }
    )
    
    # 🔥 FIX UNIVERSAL: Instruksi digeneralisasikan total untuk semua jenis video tanpa bias olahraga
    instruksi = f"""
    Target Analisis: {prompt}
    
    ATURAN KETAT AKURASI MUTLAK (UNIVERSAL ANTI-HALLUCINATION):
    1. Bertindaklah sebagai Video Editor Profesional yang sangat skeptis, objektif, dan jujur. JANGAN PERNAH BERASUMSI ATAU MENEBAK!
    2. Kamu HANYA boleh mendeteksi dan mengekstrak momen yang BENAR-BENAR terjadi secara visual 100% sesuai dengan Target Analisis: "{prompt}".
    3. Jika subjek, objek, atau aksi yang diminta di dalam prompt hanya 'hampir terjadi', berupa percobaan gagal, sudut pandang kamera terhalang/blur, atau kondisinya meragukan, CORET dan ABAIKAN! Jangan masukkan ke dalam log data.
    4. Evaluasi setiap frame secara ketat: Kriteria aksi atau objek yang diminta harus tuntas/terwujud secara penuh dan terbukti secara visual di dalam video agar sah dianggap sebagai momen yang cocok.
    5. Jawab dengan sangat singkat, padat, dan objektif untuk menghemat kuota token output.
    
    Format Output wajib berupa JSON murni tanpa markdown:
    {{"data": [{{"timestamp": "menit:detik", "description": "deskripsi objektif momen tersebut"}}]}}
    """
    
    try:
        response = model.generate_content([video_file, instruksi])
        genai.delete_file(video_file.name)
        
        raw_json = response.text.replace('```json', '').replace('```', '').strip()
        return json.loads(raw_json)
    except Exception as e:
        # Hapus file dari server Google meskipun terjadi error agar memori aman
        try: genai.delete_file(video_file.name) 
        except: pass
        
        # 🔥 TANGKAP BLOKIR SENSOR GEMINI & KIRIM KE LARAVEL
        raise Exception(f"Gemini menolak merespon. Kemungkinan diblokir oleh Safety Filter. Detail: {str(e)}")

try:
    query = f"'{folder_id}' in parents and mimeType='video/mp4' and trashed=false"
    results = drive_service.files().list(q=query, fields="files(id, name)").execute()
    items = results.get('files', [])

    semua_hasil = []
    for item in items:
        path = download_temp(item['id'], item['name'])
        hasil = analisa_video(path, user_prompt)
        
        if "data" in hasil:
            for match in hasil["data"]:
                semua_hasil.append({
                    "folder_id": folder_id,
                    "file_id": item['id'],
                    "video": item['name'],
                    "timestamp": match.get("timestamp", "0:00"),
                    "timestamp_seconds": parse_time_to_seconds(match.get("timestamp", "0:00")),
                    "description": match.get("description", "")
                })
                
        if os.path.exists(path): os.remove(path)
        time.sleep(3)

    # 🔥 WAJIB flush=True agar output tidak tertahan di buffer Windows
    print(json.dumps({"status": "success", "results": semua_hasil}), flush=True)
except Exception as e:
    # 🔥 WAJIB flush=True untuk Error
    print(json.dumps({"error": str(e)}), flush=True)