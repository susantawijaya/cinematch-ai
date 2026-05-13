import cv2
import os
import sys
import json
import time
import google.generativeai as genai
from dotenv import load_dotenv
from datetime import datetime

# Konfigurasi
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '../.env'))
api_key = os.getenv('GEMINI_API_KEY')
model_name = os.getenv('GEMINI_MODEL', 'gemini-2.5-flash')
DATABASE_FILE = 'video_metadata_index.json'

genai.configure(api_key=api_key)
model = genai.GenerativeModel(model_name)

def load_existing_index():
    if os.path.exists(DATABASE_FILE):
        with open(DATABASE_FILE, 'r') as f:
            return json.load(f)
    return {}

def save_index(data):
    with open(DATABASE_FILE, 'w') as f:
        json.dump(data, f, indent=4)

def get_video_description(frame):
    _, buffer = cv2.imencode('.jpg', frame)
    img_data = buffer.tobytes()
    
    prompt = "Deskripsikan frame video ini secara detail (objek, aksi, suasana) untuk search engine dalam 1 paragraf."
    
    max_retries = 3
    for attempt in range(max_retries):
        try:
            # Beri nafas sedikit antar request agar tidak kena rate limit
            time.sleep(4) 
            response = model.generate_content([prompt, {"mime_type": "image/jpeg", "data": img_data}])
            return response.text.strip()
        except Exception as e:
            if "429" in str(e):
                print(f"Limit tercapai, menunggu 10 detik... (Percobaan {attempt+1})")
                time.sleep(10)
                continue
            return f"Gagal: {str(e)}"
    return "Gagal setelah beberapa kali mencoba."

def run_indexing(folder_path):
    index_db = load_existing_index()
    video_extensions = ('.mp4', '.mov', '.avi')
    files = [f for f in os.listdir(folder_path) if f.lower().endswith(video_extensions)]
    
    print(f"--- Memulai Indexing {len(files)} file ---")

    for filename in files:
        if filename in index_db:
            print(f"Skipping: {filename}")
            continue

        print(f"Indexing: {filename}...")
        path = os.path.join(folder_path, filename)
        cap = cv2.VideoCapture(path)
        fps = cap.get(cv2.CAP_PROP_FPS) or 30
        
        # Sampling setiap 10 detik agar lebih hemat kuota dan stabil
        interval = int(fps * 10)
        frame_idx = 0
        video_logs = []

        while cap.isOpened():
            ret, frame = cap.read()
            if not ret: break
            
            if frame_idx % interval == 0:
                timestamp = frame_idx / fps
                desc = get_video_description(frame)
                video_logs.append({
                    "timestamp": f"{int(timestamp // 60):02d}:{int(timestamp % 60):02d}",
                    "description": desc
                })
            frame_idx += 1
        
        cap.release()
        index_db[filename] = {"last_indexed": str(datetime.now()), "data": video_logs}
        save_index(index_db)

    print(f"--- Indexing Selesai! ---")

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Gunakan: python indexer.py <path_folder_video>")
    else:
        run_indexing(sys.argv[1])