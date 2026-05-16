import React, { useState, useEffect, useRef } from 'react';

export default function SynkoraDashboard({ csrfToken, initialQuery, allAssets, chatMessages, googleToken, apiKey, clientId, error, success }) {
  const urlParams = new URLSearchParams(window.location.search);
  const currentFolderFromUrl = urlParams.get('folder_filter') || 'all';

  const [activeFolder, setActiveFolder] = useState(currentFolderFromUrl);
  const [selectedFolderId, setSelectedFolderId] = useState('');
  const [selectedFolderName, setSelectedFolderName] = useState('');
  const [promptText, setPromptText] = useState('');
  
  // 🔥 STATE BARU: Mengelola riwayat obrolan lokal secara instan & status pemrosesan AI
  const [localMessages, setLocalMessages] = useState(chatMessages);
  const [isAiThinking, setIsAiThinking] = useState(false);
  const [progressStage, setProgressStage] = useState(1);
  const [progressStatus, setProgressStatus] = useState('');

  const chatEndRef = useRef(null);

  // Sinkronisasi riwayat obrolan jika ada pembaruan data dari server
  useEffect(() => {
    setLocalMessages(chatMessages);
  }, [chatMessages]);

  // Otomatis gulung obrolan ke baris paling bawah saat ada pesan baru atau perubahan status berpikir
  useEffect(() => {
    setActiveFolder(currentFolderFromUrl);
    chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [currentFolderFromUrl, localMessages, isAiThinking]);

  // Ekstraksi Folder Utama dari database MySQL murni
  const uniqueFolders = [];
  const seenFolders = new Set();
  allAssets.forEach(asset => {
    if (asset.folder_id && !seenFolders.has(asset.folder_id)) {
      seenFolders.add(asset.folder_id);
      uniqueFolders.push({ id: asset.folder_id, label: asset.folder_name });
    }
  });

  const activeWorkspaceAssets = allAssets.filter(a => a.folder_id === activeFolder);
  const rawVideos = activeWorkspaceAssets.filter(a => a.timestamp === null);

  const subFoldersMap = {};
  activeWorkspaceAssets.forEach(asset => {
    if (asset.timestamp !== null && asset.sub_folder_name) {
      if (!subFoldersMap[asset.sub_folder_name]) {
        subFoldersMap[asset.sub_folder_name] = [];
      }
      subFoldersMap[asset.sub_folder_name].push(asset);
    }
  });

  // 🔥 FUNGSI UTAMA: Interseptor Obrolan Asinkron (AJAX Prompt Execution)
  const handleChatSubmit = async (e) => {
    e.preventDefault();
    if (!promptText.trim() || isAiThinking) return;

    const userMsg = promptText;
    setPromptText(''); // Kosongkan input bar secara instan

    // 1. Tembakkan pesan user langsung ke layar obrolan tanpa nunggu server
    setLocalMessages(prev => [...prev, { sender: 'user', message: userMsg }]);
    
    // 2. Aktifkan mode berpikir AI beserta indikator tahapannya
    setIsAiThinking(true);
    setProgressStage(1);
    setProgressStatus('Menghubungkan ke secure Google Drive folder...');

    // Siapkan data untuk dikirim ke Laravel backend via Fetch API
    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('folder_id', activeFolder);
    formData.append('prompt', userMsg);

    // 3. Sistem Simulasi Pelacak Progress (Menyelaraskan dengan siklus kerja cloud_indexer.py)
    const progressInterval = setInterval(() => {
      setProgressStage(prev => {
        if (prev < 5) {
          const next = prev + 1;
          if (next === 2) setProgressStatus('Mendownload rekaman video mentah ke area penyimpanan staging...');
          if (next === 3) setProgressStatus('Mengunggah klip video ke dalam server Gemini 2.5 Flash AI Core...');
          if (next === 4) setProgressStatus('AI sedang membaca frame gambar & menganalisis objek visual (PROCESSING)...');
          if (next === 5) setProgressStatus('Mengekstrak timeline detik, menyusun struktur sub-folder, & merapikan database...');
          return next;
        }
        return prev;
      });
    }, 8000); // Berganti tahapan secara berkala agar terlihat interaktif & profesional

    try {
      // Kirim permintaan di latar belakang (Asynchronous call)
      await fetch('/google-drive/index', {
        method: 'POST',
        body: formData
      });

      clearInterval(progressInterval);
      // 4. Setelah selesai, langsung segarkan halaman menuju folder tersebut untuk menarik sub-folder baru
      window.location.href = `/search?folder_filter=${activeFolder}`;
    } catch (err) {
      clearInterval(progressInterval);
      setIsAiThinking(false);
      alert('Koneksi terputus atau server mengalami timeout saat memproses video.');
    }
  };

  const handleOpenPicker = () => {
    if (!googleToken || typeof gapi === 'undefined') return;
    gapi.load('picker', {
      callback: () => {
        const view = new google.picker.DocsView(google.picker.ViewId.FOLDERS)
          .setMimeTypes('application/vnd.google-apps.folder').setSelectFolderEnabled(true);
        new google.picker.PickerBuilder()
          .addView(view).setOAuthToken(googleToken).setDeveloperKey(apiKey).setAppId(clientId)
          .setCallback((data) => {
            if (data.action === google.picker.Action.PICKED) {
              setSelectedFolderId(data.docs[0].id);
              setSelectedFolderName(data.docs[0].name);
            }
          }).build().setVisible(true);
      }
    });
  };

  const handleRenameFolder = (oldName) => {
    const newName = prompt("Masukkan nama baru untuk Sub-Folder ini:", oldName);
    if (!newName || newName.trim() === "") return;
    const form = document.createElement('form');
    form.method = 'POST'; form.action = '/google-drive/rename-subfolder';
    form.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}"/><input type="hidden" name="folder_id" value="${activeFolder}"/><input type="hidden" name="old_name" value="${oldName}"/><input type="hidden" name="new_name" value="${newName}"/>`;
    document.body.appendChild(form); form.submit();
  };

  const currentActiveFolderObj = uniqueFolders.find(f => f.id === activeFolder);
  const totalVideos = allAssets.length;
  const analyzedVideos = allAssets.filter(a => a.description !== null).length;
  const rawVideosCount = totalVideos - analyzedVideos;

  return (
    <div className="min-h-screen bg-[#F0EEE6] font-sans flex flex-col text-zinc-900 overflow-hidden relative">
      <div className="fixed inset-0 z-0 bg-gradient-to-br from-pink-200/40 via-indigo-100/50 to-teal-100/40 opacity-70 pointer-events-none"></div>

      {/* HEADER NAVBAR */}
      <header className="h-20 bg-white/20 backdrop-blur-xl border-b border-white/40 px-8 flex items-center justify-between shrink-0 z-30 relative shadow-sm">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-md">
            <span className="text-white font-black text-sm">S</span>
          </div>
          <div>
            <h1 className="text-lg font-bold tracking-tight leading-none text-zinc-950">Synkora <span className="text-indigo-600 font-medium text-xs">Studio Core</span></h1>
            <p className="text-[10px] text-zinc-500 font-medium mt-1">Conversational Editing Workspace</p>
          </div>
        </div>
        
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2 bg-white/60 border border-white px-3 py-1.5 rounded-full shadow-sm text-xs font-bold text-zinc-700">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Studio Active</span>
          </div>
          <form action="/logout" method="POST" className="inline">
            <input type="hidden" name="_token" value={csrfToken} />
            <button type="submit" className="bg-zinc-900 hover:bg-red-600 text-white text-xs font-bold py-2 px-4 rounded-xl transition-all shadow-md">
              Logout Account
            </button>
          </form>
        </div>
      </header>

      {/* CORE WORKSPACE SPLIT LAYOUT */}
      <div className="flex flex-1 overflow-hidden relative z-10 w-full max-w-[1600px] mx-auto">
        
        {/* SIDEBAR */}
        <aside className="w-64 border-r border-zinc-300/40 bg-white/10 backdrop-blur-md p-5 flex flex-col gap-6 shrink-0 overflow-y-auto">
          <h2 className="text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Workspaces</h2>
          <div className="flex flex-col gap-1.5">
            <a href="/search?folder_filter=all" className={`w-full px-4 py-3 text-xs font-bold rounded-xl border ${activeFolder === 'all' ? 'bg-[#F0EEE6] text-indigo-600 shadow-sm border-white' : 'text-zinc-600 border-transparent hover:bg-white/30'}`}>🌐 Studio Control Panel</a>
            {uniqueFolders.map(f => (
              <a key={f.id} href={`/search?folder_filter=${f.id}`} className={`w-full px-4 py-3 text-xs font-bold rounded-xl border truncate ${activeFolder === f.id ? 'bg-[#F0EEE6] text-indigo-600 shadow-sm border-white' : 'text-zinc-600 border-transparent hover:bg-white/30'}`}>📁 {f.label}</a>
            ))}
          </div>
        </aside>

        {/* WORKSPACE CENTER EXPANSION */}
        <main className="flex-1 p-6 flex flex-col overflow-hidden h-full">
          
          {/* KONDISI A: DASHBOARD UTAMA (ALL ACTIVE MEDIA) */}
          {activeFolder === 'all' && (
            <div className="flex flex-col flex-1 overflow-y-auto pr-1">
              <div className="bg-white/40 backdrop-blur-md border border-white p-6 rounded-3xl shadow-sm mb-6">
                <h3 className="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">Tautkan Folder Google Drive Kamera Mentah</h3>
                <form action="/google-drive/connect-folder" method="POST" className="flex gap-4">
                  <input type="hidden" name="_token" value={csrfToken} />
                  <input type="hidden" name="folder_id" value={selectedFolderId} required />
                  <button type="button" onClick={handleOpenPicker} className="bg-white hover:bg-zinc-50 text-zinc-800 font-bold py-3 px-5 rounded-xl text-xs border shadow-sm truncate max-w-[280px]">
                    📂 {selectedFolderName ? `Selected: ${selectedFolderName}` : 'Pilih Folder GDrive'}
                  </button>
                  <button type="submit" disabled={!selectedFolderId} className={`font-bold py-3 px-6 rounded-xl text-xs shadow-sm ${selectedFolderId ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'bg-zinc-300 text-zinc-500 cursor-not-allowed'}`}>Import Raw Assets</button>
                </form>
              </div>

              {/* ANALYTICS CARD VIEW */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-[#F0EEE6] p-6 rounded-2xl border border-white shadow-[6px_6px_15px_#d9d9d9,-6px_-6px_15px_#ffffff]">
                  <p className="text-[10px] font-mono font-bold uppercase text-zinc-400 mb-1">Total Connected Workspaces</p>
                  <p className="text-3xl font-black text-zinc-900">{uniqueFolders.length} <span className="text-xs font-bold text-zinc-400">Folders</span></p>
                </div>
                <div className="bg-[#F0EEE6] p-6 rounded-2xl border border-white shadow-[6px_6px_15px_#d9d9d9,-6px_-6px_15px_#ffffff]">
                  <p className="text-[10px] font-mono font-bold uppercase text-zinc-400 mb-1">AI Indexed Scenes</p>
                  <p className="text-3xl font-black text-indigo-600">{analyzedVideos} <span className="text-xs font-bold text-zinc-400">Clipped</span></p>
                </div>
                <div className="bg-[#F0EEE6] p-6 rounded-2xl border border-white shadow-[6px_6px_15px_#d9d9d9,-6px_-6px_15px_#ffffff]">
                  <p className="text-[10px] font-mono font-bold uppercase text-zinc-400 mb-1">Unprocessed Assets</p>
                  <p className="text-3xl font-black text-teal-600">{rawVideosCount} <span className="text-xs font-bold text-zinc-400">Waiting</span></p>
                </div>
              </div>
            </div>
          )}

          {/* KONDISI B: INTERNAL WORKSPACE ACTIVE WITH SIDE CHAT BOX */}
          {activeFolder !== 'all' && (
            <div className="flex-1 flex flex-col lg:flex-row gap-6 overflow-hidden h-full relative">
              
              {/* LEFT COLUMN: RAW VIDEO VIEW & CLIPS SUB-FOLDERS GROUP */}
              <div className="flex-1 overflow-y-auto pr-2 space-y-6 h-full pb-20">
                <div className="border-b border-zinc-300/40 pb-3">
                  <a href="/search?folder_filter=all" className="inline-flex items-center gap-1 text-zinc-400 hover:text-indigo-600 text-xs font-bold transition-colors mb-2 group">
                    <span className="group-hover:-translate-x-0.5 transition-transform">←</span> Back to Main Control Hub
                  </a>

                  <div className="flex justify-between items-center">
                    <div>
                      <span className="text-[10px] font-mono font-bold tracking-widest uppercase text-zinc-400">Workspace Master Folder</span>
                      <h2 className="text-lg font-black text-zinc-950">{currentActiveFolderObj?.label || 'Loading...'}</h2>
                    </div>
                    <div className="flex gap-2">
                      <form action="/google-drive/sync-folder" method="POST">
                        <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="folder_id" value={activeFolder} />
                        <button type="submit" className="bg-white hover:bg-zinc-50 border text-zinc-700 font-bold text-[11px] py-2 px-4 rounded-lg shadow-sm">🔄 Sync</button>
                      </form>
                    </div>
                  </div>
                </div>

                {/* ROW 1: DAFTAR VIDEO ASLI (RAW VIDEOS GROUP) */}
                <div>
                  <h3 className="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3 flex items-center gap-2">Aset Video Mentah ({rawVideos.length})</h3>
                  {rawVideos.length === 0 ? (
                    <p className="text-xs text-zinc-400 italic bg-white/20 p-4 rounded-xl border border-dashed border-zinc-300">Tidak ada file video mentah master.</p>
                  ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {rawVideos.map((video, i) => (
                        <div key={i} className="bg-[#F0EEE6] border border-white rounded-2xl p-4 shadow-[6px_6px_15px_#d9d9d9,-6px_-6px_15px_#ffffff]">
                          <div className="aspect-video bg-zinc-950 rounded-xl overflow-hidden relative shadow-inner border border-zinc-800">
                            <video controls preload="metadata" className="w-full h-full object-cover">
                              <source src={`/video-stream/${video.file_id}`} type="video/mp4" />
                            </video>
                          </div>
                          <div className="pt-2 flex justify-between items-center">
                            <span className="text-[11px] font-bold font-mono text-zinc-500 truncate max-w-[200px]">{video.video}</span>
                            <span className="text-[10px] font-bold text-zinc-400 bg-white border border-zinc-200 px-2 py-0.5 rounded-full">Raw Source</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* ROW 2: DAFTAR SUB-FOLDER (AI MOMENTS COMPILATIONS) */}
                <div className="border-t border-zinc-300/50 pt-6">
                  <h3 className="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">📁 AI Highlight Sub-Folders ({Object.keys(subFoldersMap).length})</h3>
                  <div className="space-y-6">
                    {Object.keys(subFoldersMap).map((folderName, i) => (
                      <div key={i} className="bg-white/40 backdrop-blur-sm border border-white p-5 rounded-[2rem] shadow-sm">
                        <div className="flex items-center justify-between border-b border-zinc-300/40 pb-3 mb-4">
                          <div className="flex items-center gap-2">
                            <span className="text-xl">📂</span>
                            <h4 className="text-sm font-black text-zinc-900 tracking-tight">{folderName}</h4>
                            <span className="text-[10px] bg-indigo-600 text-white font-bold px-2 py-0.5 rounded-full">{subFoldersMap[folderName].length} Klip</span>
                          </div>
                          <div className="flex gap-2">
                            <button onClick={() => handleRenameFolder(folderName)} className="text-[10px] font-bold bg-white border px-2.5 py-1 rounded-lg text-zinc-600 hover:bg-zinc-50 shadow-sm">✏️ Rename</button>
                            <form action="/google-drive/delete-subfolder" method="POST">
                              <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="folder_id" value={activeFolder} /><input type="hidden" name="sub_folder_name" value={folderName} />
                              <button type="submit" className="text-[10px] font-bold bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg text-red-700 hover:bg-red-100 shadow-sm">🗑️ Delete Folder</button>
                            </form>
                          </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          {subFoldersMap[folderName].map((clip, idx) => (
                            <div key={idx} className="bg-[#F0EEE6] border border-white/60 p-4 rounded-xl flex flex-col justify-between shadow-sm">
                              <div className="aspect-video bg-zinc-950 rounded-lg overflow-hidden relative mb-3 border border-zinc-800">
                                <video controls preload="metadata" className="w-full h-full object-cover">
                                  <source src={`/video-stream/${clip.file_id}#t=${clip.timestamp_seconds}`} type="video/mp4" />
                                </video>
                                <div className="absolute top-2 right-2 bg-black/70 text-white font-mono font-bold text-[9px] px-2 py-0.5 rounded">⏱️ {clip.timestamp}</div>
                              </div>
                              <div>
                                <div className="flex justify-between items-center mb-1">
                                  <span className="text-[10px] font-bold font-mono text-zinc-400 truncate max-w-[150px]">{clip.video}</span>
                                  <form action="/google-drive/delete-clip" method="POST">
                                    <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="folder_id" value={activeFolder} /><input type="hidden" name="clip_id" value={clip.id} />
                                    <button type="submit" className="text-[9px] text-red-600 font-bold hover:underline">❌ Buang Klip</button>
                                  </form>
                                </div>
                                <p className="text-[11px] text-zinc-600 font-medium leading-relaxed bg-white/50 p-2.5 rounded-lg border border-white/40">{clip.description}</p>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* RIGHT COLUMN: KOTAK CHAT PROMPT & HISTORY (AI ASSISTANT PANELS) */}
              <div className="w-full lg:w-96 bg-white/40 backdrop-blur-md border border-white rounded-[2rem] p-4 flex flex-col justify-between h-[calc(100vh-140px)] shrink-0 shadow-sm relative z-20">
                <div className="border-b border-zinc-300/40 pb-2 mb-3">
                  <h3 className="text-xs font-black text-zinc-900 tracking-tight uppercase flex items-center gap-1.5">⚡ Synkora AI Assistant</h3>
                  <p className="text-[9px] text-zinc-400 font-medium font-mono">Real-time continuous prompt session</p>
                </div>

                {/* Ruang Log Percakapan */}
                <div className="flex-1 overflow-y-auto space-y-3 mb-4 pr-1">
                  {localMessages.map((msg, i) => (
                    <div key={i} className={`flex flex-col ${msg.sender === 'user' ? 'items-end' : 'items-start'}`}>
                      <span className="text-[9px] font-bold text-zinc-400 font-mono mb-0.5 uppercase">{msg.sender === 'user' ? 'Santa (You)' : 'Synkora Core'}</span>
                      <div className={`max-w-[85%] rounded-2xl px-4 py-2.5 text-xs font-medium leading-relaxed shadow-sm ${msg.sender === 'user' ? 'bg-zinc-950 text-white rounded-tr-none' : 'bg-white text-zinc-800 border rounded-tl-none'}`}>
                        {msg.message}
                      </div>
                    </div>
                  ))}

                  {/* 🔥 INTERFACE SPINNER BARU: Muncul secara real-time tepat setelah user menekan Send */}
                  {isAiThinking && (
                    <div className="flex flex-col items-start animate-pulse">
                      <span className="text-[9px] font-bold text-indigo-600 font-mono mb-0.5 uppercase">Synkora Core (Thinking)</span>
                      <div className="max-w-[85%] bg-white text-zinc-800 border rounded-2xl rounded-tl-none p-4 text-xs font-medium shadow-md space-y-2.5">
                        <div className="flex items-center gap-2 text-indigo-600 font-bold font-mono text-[10px]">
                          <svg className="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                          </svg>
                          <span>ANALYSIS CORE STAGE {progressStage}/5</span>
                        </div>
                        <p className="text-zinc-500 text-[11px] font-semibold leading-relaxed transition-all duration-500">{progressStatus}</p>
                      </div>
                    </div>
                  )}
                  <div ref={chatEndRef} />
                </div>

                {/* Form Input Kirim Obrolan */}
                <form onSubmit={handleChatSubmit} className="shrink-0">
                  <div className="flex gap-2 bg-[#F0EEE6] rounded-xl p-1.5 border border-white shadow-inner">
                    <input 
                      type="text" 
                      value={promptText}
                      onChange={(e) => setPromptText(e.target.value)}
                      placeholder={isAiThinking ? "AI sedang bekerja memproses..." : "Tanya / suruh AI disini..."} 
                      disabled={isAiThinking}
                      required 
                      className="flex-1 bg-transparent border-none text-xs px-2 py-2 focus:outline-none placeholder-zinc-400 text-zinc-900 font-medium disabled:cursor-not-allowed"
                    />
                    <button 
                      type="submit" 
                      disabled={isAiThinking}
                      className={`px-4 rounded-lg text-xs font-bold transition-colors shadow-sm flex items-center justify-center ${isAiThinking ? 'bg-zinc-300 text-zinc-400 cursor-not-allowed' : 'bg-zinc-950 text-white hover:bg-indigo-600'}`}
                    >
                      Send
                    </button>
                  </div>
                </form>
              </div>

            </div>
          )}
        </main>
      </div>
    </div>
  );
}