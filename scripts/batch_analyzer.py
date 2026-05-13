import cv2
import os
import sys
import json
import google.generativeai as genai
from dotenv import load_dotenv
from datetime import datetime

# 1. Konfigurasi Environment
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '../.env'))
api_key = os.getenv('GEMINI_API_KEY')
model_name = os.getenv('GEMINI_MODEL', 'gemini-2.5-flash')

if not api_key:
    print("Error: API Key tidak ditemukan di .env")
    sys.exit(1)

genai.configure(api_key=api_key)
model = genai.GenerativeModel(model_name)

def is_blurry(image, threshold=70.0):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    fm = cv2.Laplacian(gray, cv2.CV_64F).var()
    return fm < threshold

def is_bad_exposure(image):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    mean_brightness = gray.mean()
    return mean_brightness < 40 or mean_brightness > 240

def process_single_video(video_path, user_prompt):
    cap = cv2.VideoCapture(video_path)
    fps = cap.get(cv2.CAP_PROP_FPS)
    if fps == 0: fps = 30
    
    interval = int(fps * 2) 
    frame_idx = 0
    matches = []

    while cap.isOpened():
        ret, frame = cap.read()
        if not ret: break

        if frame_idx % interval == 0:
            timestamp = frame_idx / fps
            if is_blurry(frame) or is_bad_exposure(frame):
                frame_idx += 1
                continue
            
            _, buffer = cv2.imencode('.jpg', frame)
            img_data = buffer.tobytes()
            
            # Perbaikan: Menggunakan single quote untuk f-string agar double quote JSON aman
            full_prompt = f'Instruksi: {user_prompt}. Jawab HANYA dengan JSON murni: {{"found": true, "desc": "alasan"}}'
            
            try:
                response = model.generate_content([
                    full_prompt,
                    {"mime_type": "image/jpeg", "data": img_data}
                ])
                raw_res = response.text.replace('```json', '').replace('```', '').strip()
                data = json.loads(raw_res)
                if data.get("found"):
                    matches.append({
                        "time": f"{int(timestamp // 60):02d}:{int(timestamp % 60):02d}",
                        "seconds": round(timestamp, 2),
                        "description": data.get("desc")
                    })
            except Exception:
                continue
        frame_idx += 1
    cap.release()
    return matches

def batch_process(folder_path, user_prompt):
    if not os.path.isdir(folder_path):
        print(f"Error: Folder {folder_path} tidak ditemukan.")
        return

    video_extensions = ('.mp4', '.mov', '.avi', '.mkv')
    files = [f for f in os.listdir(folder_path) if f.lower().endswith(video_extensions)]
    
    print(f"--- Menemukan {len(files)} video di {folder_path} ---")
    all_reports = []

    for filename in files:
        path = os.path.join(folder_path, filename)
        print(f"Memproses: {filename}...")
        results = process_single_video(path, user_prompt)
        if results:
            all_reports.append({"video": filename, "events": results})

    output_filename = f"batch_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    with open(output_filename, 'w') as f:
        json.dump(all_reports, f, indent=4)
    print(f"--- Selesai! Laporan disimpan di: {output_filename} ---")

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Gunakan: python batch_analyzer.py <path_folder> <prompt>")
    else:
        batch_process(sys.argv[1], sys.argv[2])