import os
import sys
import json
import subprocess
import time
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build
from googleapiclient.http import MediaIoBaseDownload

sys.stdout.reconfigure(encoding='utf-8')

try:
    access_token = sys.argv[1]
    # Parameter ke-2 sekarang langsung JSON karena durasi nempel di masing-masing klip
    clips_json_str = sys.argv[2]
    clips = json.loads(clips_json_str)

    creds = Credentials(token=access_token)
    drive_service = build('drive', 'v3', credentials=creds)

    temp_dir = os.path.join(os.path.dirname(__file__), '../storage/app/temp_videos')
    out_dir = os.path.join(os.path.dirname(__file__), '../storage/app/public/merged')
    os.makedirs(temp_dir, exist_ok=True)
    os.makedirs(out_dir, exist_ok=True)

    cut_files = []
    
    # PROSES 1: Download & Potong Presisi per Klip (Durasi Dinamis)
    for i, clip in enumerate(clips):
        file_id = clip['file_id']
        start_time = clip['timestamp_seconds']
        clip_duration = clip.get('duration', 3) # Ambil durasi individual, default 3

        temp_in = os.path.join(temp_dir, f"dl_{i}.mp4")
        
        request = drive_service.files().get_media(fileId=file_id)
        with open(temp_in, 'wb') as fh:
            downloader = MediaIoBaseDownload(fh, request)
            done = False
            while not done: _, done = downloader.next_chunk()

        temp_out = os.path.join(temp_dir, f"cut_{i}.mp4")
        cmd = [
            'ffmpeg', '-y', '-ss', str(start_time), '-i', temp_in, '-t', str(clip_duration),
            '-vf', 'scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2,fps=30',
            '-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '28',
            '-c:a', 'aac', '-ar', '44100', '-ac', '2', temp_out
        ]
        subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        
        cut_files.append(temp_out)
        os.remove(temp_in) 

    # PROSES 2: Jahit / Gabungkan Semua Klip
    list_file = os.path.join(temp_dir, 'list.txt')
    with open(list_file, 'w', encoding='utf-8') as f:
        for cut_file in cut_files:
            f.write(f"file '{os.path.basename(cut_file)}'\n")

    final_out_name = f"Synkora_Merged_{int(time.time())}.mp4"
    final_out_path = os.path.join(out_dir, final_out_name)

    concat_cmd = [
        'ffmpeg', '-y', '-f', 'concat', '-safe', '0', '-i', list_file,
        '-c', 'copy', final_out_path
    ]
    subprocess.run(concat_cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

    for cut_file in cut_files: 
        if os.path.exists(cut_file): os.remove(cut_file)
    if os.path.exists(list_file): os.remove(list_file)

    print(json.dumps({"status": "success", "file_url": f"/storage/merged/{final_out_name}"}))

except Exception as e:
    error_msg = str(e).lower()
    if "refresh" in error_msg or "credentials" in error_msg or "unauthorized" in error_msg:
        print(json.dumps({
            "status": "error", 
            "message": "Sesi koneksi Google Drive kamu telah habis. Silakan klik 'Logout Account' lalu Login kembali."
        }))
    else:
        print(json.dumps({"status": "error", "message": str(e)}))