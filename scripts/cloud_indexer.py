import google.generativeai as genai
import os
import time

# Konfigurasi API
genai.configure(api_key="API_KEY_KAMU")

def index_video_cloud(file_path):
    print(f"Mengunggah ke Cloud: {file_path}...")
    
    # 1. Upload file ke Gemini File API
    # File ini akan otomatis dihapus oleh Google setelah 48 jam (aman & bersih)
    video_file = genai.upload_file(path=file_path)
    
    # 2. Tunggu proses pemrosesan di server Google
    while video_file.state.name == "PROCESSING":
        print(".", end="", flush=True)
        time.sleep(5)
        video_file = genai.get_file(video_file.name)

    if video_file.state.name == "FAILED":
        raise Exception("Gagal memproses video di server Google.")

    print("\nGoogle selesai menonton video. Menganalisis momen...")

    # 3. Minta AI mencari semua momen epic
    model = genai.GenerativeModel(model_name="gemini-1.5-flash")
    prompt = """
    Tonton video ini dan buatkan daftar momen penting dalam format JSON.
    Cari momen seperti: bola masuk ring, selebrasi, jump shot, atau kerumunan penonton.
    Format JSON: {"data": [{"timestamp": "menit:detik", "description": "penjelasan detail"}]}
    """
    
    response = model.generate_content([video_file, prompt])
    return response.text

# Contoh penggunaan
# video_json = index_video_cloud("path/ke/video/dari/gdrive.mp4")
# print(video_json)