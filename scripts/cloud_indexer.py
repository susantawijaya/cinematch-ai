import os
import sys
import json
import time
import cv2
import numpy as np
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build
from googleapiclient.http import MediaIoBaseDownload
import google.generativeai as genai
from dotenv import load_dotenv

sys.stdout.reconfigure(encoding='utf-8')

# Path library milik LENOVO
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

def smart_filter_pass(file_path):
    cap = cv2.VideoCapture(file_path)
    if not cap.isOpened():
        return False, "Video rusak/tidak bisa dibaca."

    fps = cap.get(cv2.CAP_PROP_FPS)
    frame_count = cap.get(cv2.CAP_PROP_FRAME_COUNT)

    if fps == 0 or frame_count == 0:
        cap.release()
        return False, "Metadata video kosong."

    duration = frame_count / fps
    if duration < 2.0:
        cap.release()
        return False, f"Video terlalu pendek ({round(duration, 1)} detik)."

    brightness_list = []
    movement_list = []
    prev_frame = None

    skip_frames = int(fps) 
    
    current_frame = 0
    while True:
        ret, frame = cap.read()
        if not ret:
            break

        if current_frame % skip_frames == 0:
            small_frame = cv2.resize(frame, (320, 240))
            gray = cv2.cvtColor(small_frame, cv2.COLOR_BGR2GRAY)
            brightness_list.append(np.mean(gray))

            if prev_frame is not None:
                diff = cv2.absdiff(gray, prev_frame)
                movement_list.append(np.mean(diff))

            prev_frame = gray
            
        current_frame += 1

    cap.release()

    avg_brightness = np.mean(brightness_list) if brightness_list else 0
    if avg_brightness < 15: 
        return False, f"Video terlalu gelap (Skor Brightness: {round(avg_brightness, 1)})."

    avg_movement = np.mean(movement_list) if movement_list else 0
    if avg_movement > 70: 
        return False, f"Guncangan ekstrem terdeteksi (Skor Movement: {round(avg_movement, 1)})."

    return True, "Lolos sensor."

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
    
    # 🔥 FASE 4: VIBE SCORE & SEMANTIC TAGS INJECTION
    instruksi = f"""
    Target Analisis: {prompt}
    
    ATURAN KETAT AKURASI MUTLAK:
    1. Bertindaklah sebagai Video Editor Profesional yang sangat skeptis, objektif, dan jujur. JANGAN PERNAH BERASUMSI ATAU MENEBAK!
    2. Kamu HANYA boleh mendeteksi momen yang BENAR-BENAR terjadi secara visual 100% sesuai dengan Target Analisis.
    3. JANGAN LAKUKAN OVER-REPORTING! Jika aksi terjadi terus-menerus, MENCATAT 1 KALI SAJA di titik AWAL dimulainya aksi.
    4. Dilarang keras memecah 1 adegan/momen yang berlanjutan menjadi beberapa timestamp.
    5. VIBE SCORE (1-100): Berikan skor intensitas adegan. 100 = sangat epik/emosional/aksi tinggi, 1 = membosankan/diam.
    6. SEMANTIC TAGS: Berikan 1-3 hashtag yang paling merepresentasikan makna tersembunyi atau emosi (contoh: ["#tegang", "#kemenangan"]).
    
    Format Output wajib berupa JSON murni tanpa markdown:
    {{"data": [{{"timestamp": "menit:detik", "description": "deskripsi singkat objektif dari awal momen ini", "vibe_score": 85, "semantic_tags": ["#tag1", "#tag2"]}}]}}
    """
    
    try:
        response = model.generate_content([video_file, instruksi])
        genai.delete_file(video_file.name)
        
        raw_json = response.text.replace('```json', '').replace('```', '').strip()
        return json.loads(raw_json)
    except Exception as e:
        try: genai.delete_file(video_file.name) 
        except: pass
        raise Exception(f"Gemini menolak merespon. Kemungkinan diblokir oleh Safety Filter. Detail: {str(e)}")

try:
    query = f"'{folder_id}' in parents and mimeType='video/mp4' and trashed=false"
    results = drive_service.files().list(q=query, fields="files(id, name)").execute()
    items = results.get('files', [])

    semua_hasil = []
    stats = {"total": len(items), "filtered": 0, "analyzed": 0}

    for item in items:
        path = download_temp(item['id'], item['name'])
        
        is_valid, reason = smart_filter_pass(path)
        
        if not is_valid:
            stats["filtered"] += 1
            if os.path.exists(path): os.remove(path)
            continue 
            
        stats["analyzed"] += 1
        hasil = analisa_video(path, user_prompt)
        
        if "data" in hasil:
            sorted_data = sorted(hasil["data"], key=lambda x: parse_time_to_seconds(x.get("timestamp", "0:00")))
            last_sec = -999 
            
            for match in sorted_data:
                current_sec = parse_time_to_seconds(match.get("timestamp", "0:00"))
                
                if abs(current_sec - last_sec) < 10:
                    continue
                
                last_sec = current_sec 

                semua_hasil.append({
                    "folder_id": folder_id,
                    "file_id": item['id'],
                    "video": item['name'],
                    "timestamp": match.get("timestamp", "0:00"),
                    "timestamp_seconds": current_sec,
                    "description": match.get("description", ""),
                    "vibe_score": match.get("vibe_score", 50),
                    "semantic_tags": match.get("semantic_tags", [])
                })
                
        if os.path.exists(path): os.remove(path)
        time.sleep(3)

    print(json.dumps({"status": "success", "results": semua_hasil, "stats": stats}), flush=True)
except Exception as e:
    print(json.dumps({"error": str(e)}), flush=True)