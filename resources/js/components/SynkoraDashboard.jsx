import React, { useState, useEffect, useRef } from 'react';

export default function SynkoraDashboard({ csrfToken, initialQuery, allAssets, chatMessages, googleToken, apiKey, clientId, error, success }) {
  const urlParams = new URLSearchParams(window.location.search);
  const currentFolderFromUrl = urlParams.get('folder_filter') || 'all';

  const [activeFolder, setActiveFolder] = useState(currentFolderFromUrl);
  const [selectedFolderId, setSelectedFolderId] = useState('');
  const [selectedFolderName, setSelectedFolderName] = useState('');
  const [promptText, setPromptText] = useState('');
  
  const [searchQuery, setSearchQuery] = useState('');
  const [localMessages, setLocalMessages] = useState(chatMessages);
  const [localAssets, setLocalAssets] = useState(allAssets);
  const [isAiThinking, setIsAiThinking] = useState(false);
  const [progressStage, setProgressStage] = useState(1);
  const [progressStatus, setProgressStatus] = useState('');

  const [collapsedSections, setCollapsedSections] = useState({});
  const chatEndRef = useRef(null);
  
  // 🔥 REFERENSI BARU: Untuk mengontrol penghentian proses AI di tengah jalan
  const abortControllerRef = useRef(null);
  const progressIntervalRef = useRef(null);

  const [modal, setModal] = useState({
    isOpen: false, type: 'alert', title: '', message: '', inputValue: '',
    confirmText: 'OK', cancelText: 'Batal', isDestructive: false, onConfirm: null
  });

  const closeModal = () => setModal({ ...modal, isOpen: false });

  useEffect(() => {
    setLocalMessages(chatMessages);
    setLocalAssets(allAssets);
  }, [chatMessages, allAssets]);

  useEffect(() => {
    setActiveFolder(currentFolderFromUrl);
    setSearchQuery('');
    setCollapsedSections({}); 
    chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [currentFolderFromUrl, localMessages, isAiThinking]);

  const toggleSection = (sectionKey) => {
    setCollapsedSections(prev => ({ ...prev, [sectionKey]: !prev[sectionKey] }));
  };

  const uniqueFolders = [];
  const seenFolders = new Set();
  localAssets.forEach(asset => {
    if (asset.folder_id && !seenFolders.has(asset.folder_id)) {
      seenFolders.add(asset.folder_id);
      uniqueFolders.push({ id: asset.folder_id, label: asset.folder_name });
    }
  });

  const activeWorkspaceAssets = localAssets.filter(a => a.folder_id === activeFolder);
  
  const filteredAssets = activeWorkspaceAssets.filter(asset => {
    if (!searchQuery) return true;
    const lowerQuery = searchQuery.toLowerCase();
    const matchName = asset.video.toLowerCase().includes(lowerQuery);
    const matchDesc = asset.description ? asset.description.toLowerCase().includes(lowerQuery) : false;
    const matchFolder = asset.sub_folder_name ? asset.sub_folder_name.toLowerCase().includes(lowerQuery) : false;
    return matchName || matchDesc || matchFolder;
  });

  const rawVideos = filteredAssets.filter(a => a.timestamp === null);

  const subFoldersMap = {};
  filteredAssets.forEach(asset => {
    if (asset.timestamp !== null && asset.sub_folder_name) {
      if (!subFoldersMap[asset.sub_folder_name]) {
        subFoldersMap[asset.sub_folder_name] = [];
      }
      subFoldersMap[asset.sub_folder_name].push(asset);
    }
  });

  const handleRenameFolder = (oldName) => {
    setModal({
      isOpen: true, type: 'prompt', title: 'Ubah Nama Folder',
      message: 'Ketik nama baru untuk mengelompokkan klip ini:',
      inputValue: oldName, confirmText: 'Simpan Nama', isDestructive: false,
      onConfirm: async (newName) => {
        if (!newName || newName.trim() === "" || newName === oldName) return;
        const formData = new FormData();
        formData.append('_token', csrfToken); formData.append('folder_id', activeFolder);
        formData.append('old_name', oldName); formData.append('new_name', newName);
        try {
          const response = await fetch('/google-drive/rename-subfolder', { method: 'POST', body: formData });
          const data = await response.json();
          if (data.all_assets) setLocalAssets(data.all_assets);
        } catch (err) { console.error(err); }
      }
    });
  };

  const handleDeleteSubFolder = (folderName) => {
    setModal({
      isOpen: true, type: 'confirm', title: 'Hapus Kumpulan Klip',
      message: `Apakah kamu yakin ingin menghapus folder "${folderName}" beserta seluruh isinya?`,
      confirmText: 'Ya, Hapus Folder', isDestructive: true,
      onConfirm: async () => {
        const formData = new FormData();
        formData.append('_token', csrfToken); formData.append('folder_id', activeFolder);
        formData.append('sub_folder_name', folderName);
        try {
          const response = await fetch('/google-drive/delete-subfolder', { method: 'POST', body: formData });
          const data = await response.json();
          if (data.all_assets) setLocalAssets(data.all_assets);
        } catch (err) { console.error(err); }
      }
    });
  };

  const handleDeleteClip = (clipId) => {
    setModal({
      isOpen: true, type: 'confirm', title: 'Buang Momen Klip',
      message: 'Klip video ini akan dibuang dari daftar hasil AI. Yakin melanjutkan?',
      confirmText: 'Buang Klip', isDestructive: true,
      onConfirm: async () => {
        const formData = new FormData();
        formData.append('_token', csrfToken); formData.append('folder_id', activeFolder);
        formData.append('clip_id', clipId);
        try {
          const response = await fetch('/google-drive/delete-clip', { method: 'POST', body: formData });
          const data = await response.json();
          if (data.all_assets) setLocalAssets(data.all_assets);
        } catch (err) { console.error(err); }
      }
    });
  };

  const handleClearChat = () => {
    setModal({
      isOpen: true, type: 'confirm', title: 'Mulai Obrolan Baru',
      message: 'Ini akan menghapus seluruh riwayat percakapan di workspace ini dan membersihkan layar. Lanjutkan?',
      confirmText: 'Ya, Bersihkan Layar', isDestructive: true,
      onConfirm: async () => {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('folder_id', activeFolder);
        try {
          await fetch('/chat/clear', { method: 'POST', body: formData });
          setLocalMessages([]); 
        } catch (err) { console.error(err); }
      }
    });
  };

  const [isSyncing, setIsSyncing] = useState(false);
  const handleSyncWorkspace = async () => {
    setIsSyncing(true);
    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('folder_id', activeFolder);
    try {
      const response = await fetch('/google-drive/sync-folder', { 
        method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await response.json();
      if (data.all_assets) setLocalAssets(data.all_assets);
    } catch (err) { console.error(err); }
    setIsSyncing(false);
  };

  const handleDeleteWorkspace = () => {
    setModal({
      isOpen: true, type: 'confirm', title: 'Hapus Koneksi Workspace',
      message: 'Seluruh data klip dan histori obrolan untuk folder ini akan dihapus dari sistem (File di Google Drive tetap aman). Lanjutkan?',
      confirmText: 'Ya, Putus Koneksi', isDestructive: true,
      onConfirm: async () => {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('folder_id', activeFolder);
        try {
          await fetch('/google-drive/delete-folder', { 
            method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } 
          });
          window.location.href = '/search?folder_filter=all';
        } catch (err) { console.error(err); }
      }
    });
  };

  // 🔥 FITUR BARU: FUNGSI UNTUK MEMBATALKAN PROSES AI
  const handleStopPrompt = () => {
    if (abortControllerRef.current) {
      abortControllerRef.current.abort(); // Putus koneksi HTTP ke server secara paksa
    }
    if (progressIntervalRef.current) {
      clearInterval(progressIntervalRef.current);
    }
    setIsAiThinking(false);
    setLocalMessages(prev => [
      ...prev, 
      { sender: 'ai', message: '⛔ Proses analisis telah dibatalkan secara manual.' }
    ]);
  };

  // 🔥 FITUR BARU: FUNGSI UNTUK MENGEDIT PROMPT
  const handleEditPrompt = (text) => {
    setPromptText(text);
    // Kita biarkan history-nya, tapi textnya kembali ke input, user bisa langsung edit & enter lagi
  };

  const handleChatSubmit = async (e) => {
    e.preventDefault();
    if (!promptText.trim() || isAiThinking) return;

    const userMsg = promptText;
    setPromptText('');
    setLocalMessages(prev => [...prev, { sender: 'user', message: userMsg }]);
    
    setIsAiThinking(true);
    setProgressStage(1);
    setProgressStatus('Menghubungkan ke secure Google Drive folder...');

    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('folder_id', activeFolder);
    formData.append('prompt', userMsg);

    // Inisialisasi AbortController baru untuk request ini
    abortControllerRef.current = new AbortController();
    progressIntervalRef.current = setInterval(() => {
      setProgressStage(prev => {
        if (prev < 5) {
          const next = prev + 1;
          if (next === 2) setProgressStatus('Mendownload rekaman video mentah ke area penyimpanan staging...');
          if (next === 3) setProgressStatus('Mengunggah klip video ke dalam server Gemini AI Core...');
          if (next === 4) setProgressStatus('AI sedang membaca frame gambar & menganalisis objek visual (PROCESSING)...');
          if (next === 5) setProgressStatus('Mengekstrak timeline detik, menyusun struktur sub-folder...');
          return next;
        }
        return prev;
      });
    }, 6000);

    try {
      const response = await fetch('/google-drive/index', { 
        method: 'POST', 
        body: formData,
        signal: abortControllerRef.current.signal // 🔥 Hubungkan sinyal Abort
      });
      const data = await response.json();
      
      clearInterval(progressIntervalRef.current);
      setIsAiThinking(false);

      if (data.chat_messages) setLocalMessages(data.chat_messages);
      if (data.all_assets) setLocalAssets(data.all_assets);

    } catch (err) {
      clearInterval(progressIntervalRef.current);
      setIsAiThinking(false);

      // Cek apakah error karena user menekan tombol Stop (AbortError)
      if (err.name === 'AbortError') {
        console.log('Permintaan dibatalkan oleh pengguna.');
        // Pesan pembatalan sudah di-handle oleh handleStopPrompt()
      } else {
        setModal({
          isOpen: true, type: 'alert', title: 'Koneksi Terputus',
          message: 'Server mengalami timeout atau koneksi internet terputus saat memproses instruksi. Silakan coba lagi.',
          confirmText: 'Tutup', isDestructive: false, onConfirm: null
        });
      }
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

  const exportEDL = (filename, timestamp) => {
    const timeParts = timestamp.split(':');
    const seconds = parseInt(timeParts[0]) * 60 + parseInt(timeParts[1]);
    const startTime = new Date(seconds * 1000).toISOString().substr(11, 8) + ":00";
    const endTime = new Date((seconds + 5) * 1000).toISOString().substr(11, 8) + ":00";
    const edlContent = `TITLE: SYNKORA\nFCM: NON-DROP FRAME\n\n001 AX V C ${startTime} ${endTime} ${startTime} ${endTime}\n* FROM CLIP NAME: ${filename}`;
    const blob = new Blob([edlContent], { type: 'text/plain' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename.replace('.mp4','') + '.edl'; a.click();
  };

  const currentActiveFolderObj = uniqueFolders.find(f => f.id === activeFolder);
  const totalVideos = localAssets.length;
  const analyzedVideos = localAssets.filter(a => a.description !== null).length;
  const rawVideosCount = totalVideos - analyzedVideos;
  const showRawSection = rawVideos.length > 0 || !searchQuery;

  return (
    <div className="h-screen bg-[#F0EEE6] font-sans flex flex-col text-zinc-900 overflow-hidden relative">
      <div className="fixed inset-0 z-0 bg-gradient-to-br from-pink-200/40 via-indigo-100/50 to-teal-100/40 opacity-70 pointer-events-none"></div>

      {modal.isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-sm transition-opacity">
          <div className="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-white flex flex-col gap-4 transform transition-all scale-100 opacity-100 relative">
            
            <button 
              onClick={closeModal} 
              className="absolute top-5 right-5 text-zinc-400 hover:text-red-500 bg-zinc-100 hover:bg-red-50 p-1.5 rounded-full transition-all focus:outline-none"
              title="Tutup"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>

            <div className="pr-6">
              <h3 className="text-lg font-black text-zinc-900 tracking-tight">{modal.title}</h3>
              <p className="text-[11px] text-zinc-500 font-medium mt-1 leading-relaxed">{modal.message}</p>
            </div>

            {modal.type === 'prompt' && (
              <input
                type="text"
                value={modal.inputValue}
                onChange={(e) => setModal({ ...modal, inputValue: e.target.value })}
                onKeyDown={(e) => e.key === 'Enter' && (modal.onConfirm(modal.inputValue), closeModal())}
                className="w-full bg-[#F0EEE6] text-zinc-900 px-4 py-3 rounded-xl text-xs font-bold shadow-inner border border-white focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-all"
                autoFocus
              />
            )}

            <div className="flex justify-end gap-2 mt-2">
              {modal.type !== 'alert' && (
                <button onClick={closeModal} className="px-4 py-2.5 rounded-xl text-xs font-bold text-zinc-600 hover:bg-zinc-100 transition-colors">
                  {modal.cancelText}
                </button>
              )}
              <button
                onClick={() => {
                  if (modal.onConfirm) { modal.type === 'prompt' ? modal.onConfirm(modal.inputValue) : modal.onConfirm(); }
                  closeModal();
                }}
                className={`px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm transition-colors ${modal.isDestructive ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-zinc-900 hover:bg-indigo-600 text-white'}`}
              >
                {modal.confirmText}
              </button>
            </div>
          </div>
        </div>
      )}

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

      <div className="flex flex-1 overflow-hidden relative z-10 w-full max-w-[1600px] mx-auto">
        <aside className="w-64 border-r border-zinc-300/40 bg-white/10 backdrop-blur-md p-5 flex flex-col gap-6 shrink-0 overflow-y-auto">
          <h2 className="text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Workspaces</h2>
          <div className="flex flex-col gap-1.5">
            <a href="/search?folder_filter=all" className={`w-full px-4 py-3 text-xs font-bold rounded-xl border ${activeFolder === 'all' ? 'bg-[#F0EEE6] text-indigo-600 shadow-sm border-white' : 'text-zinc-600 border-transparent hover:bg-white/30'}`}>🌐 Studio Control Panel</a>
            {uniqueFolders.map(f => (
              <a key={f.id} href={`/search?folder_filter=${f.id}`} className={`w-full px-4 py-3 text-xs font-bold rounded-xl border truncate ${activeFolder === f.id ? 'bg-[#F0EEE6] text-indigo-600 shadow-sm border-white' : 'text-zinc-600 border-transparent hover:bg-white/30'}`}>📁 {f.label}</a>
            ))}
          </div>
        </aside>

        {activeFolder === 'all' ? (
          <main className="flex-1 overflow-y-auto p-6">
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

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
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
          </main>
        ) : (
          <>
            <main className="flex-1 overflow-y-auto p-6 pb-20">
              <div className="border-b border-zinc-300/40 pb-3 mb-5">
                <a href="/search?folder_filter=all" className="inline-flex items-center gap-1 text-zinc-400 hover:text-indigo-600 text-xs font-bold transition-colors mb-2 group">
                  <span className="group-hover:-translate-x-0.5 transition-transform">←</span> Back to Main Control Hub
                </a>

                <div className="flex justify-between items-center">
                  <div>
                    <span className="text-[10px] font-mono font-bold tracking-widest uppercase text-zinc-400">Workspace Master Folder</span>
                    <h2 className="text-lg font-black text-zinc-950">{currentActiveFolderObj?.label || 'Loading...'}</h2>
                  </div>
                  <div className="flex gap-2">
                    <button 
                      onClick={handleSyncWorkspace} 
                      disabled={isSyncing}
                      className={`bg-white hover:bg-zinc-50 border text-zinc-700 font-bold text-[11px] py-2 px-4 rounded-lg shadow-sm transition-colors ${isSyncing ? 'opacity-50 cursor-not-allowed' : ''}`}
                    >
                      {isSyncing ? '🔄 Menyinkronkan...' : '🔄 Sync'}
                    </button>
                    <button 
                      onClick={handleDeleteWorkspace} 
                      className="bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 font-bold text-[11px] py-2 px-4 rounded-lg shadow-sm transition-colors"
                    >
                      🗑️ Delete
                    </button>
                  </div>
                </div>
              </div>

              <div className="mb-6 relative">
                <input 
                  type="text" 
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="🔍 Cari momen klip, nama folder, atau judul video di sini (contoh: melempar)..." 
                  className="w-full bg-white/50 backdrop-blur-sm text-zinc-900 px-5 py-3 rounded-2xl text-xs font-bold shadow-sm border border-white/60 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-all placeholder-zinc-400"
                />
              </div>

              {filteredAssets.length === 0 ? (
                 <div className="text-center py-12 bg-white/20 border border-dashed border-zinc-300 rounded-2xl">
                   <p className="text-xs text-zinc-500 font-bold">Pencarian "{searchQuery}" tidak ditemukan di folder ini.</p>
                 </div>
              ) : (
                <>
                  {showRawSection && (
                    <div className="mb-6">
                      <div 
                        className="flex items-center gap-2 cursor-pointer group w-max mb-3 select-none"
                        onClick={() => toggleSection('raw')}
                      >
                        <div className="w-5 h-5 flex items-center justify-center rounded-md bg-white/50 group-hover:bg-indigo-100 text-zinc-500 group-hover:text-indigo-600 transition-colors shadow-sm border border-zinc-200">
                          {collapsedSections['raw'] ? (
                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M9 5l7 7-7 7"></path></svg>
                          ) : (
                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M19 9l-7 7-7-7"></path></svg>
                          )}
                        </div>
                        <h3 className="text-xs font-bold uppercase tracking-wider text-zinc-400 group-hover:text-indigo-600 transition-colors m-0">🎞️ Aset Video Mentah ({rawVideos.length})</h3>
                      </div>

                      {!collapsedSections['raw'] && (
                        rawVideos.length === 0 ? (
                          <p className="text-[11px] text-zinc-400 italic bg-white/20 p-4 rounded-xl border border-dashed border-zinc-300">Video mentah master kosong.</p>
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
                        )
                      )}
                    </div>
                  )}

                  {Object.keys(subFoldersMap).length > 0 && (
                    <div className="border-t border-zinc-300/50 pt-6">
                      
                      <div 
                        className="flex items-center gap-2 cursor-pointer group w-max mb-4 select-none"
                        onClick={() => toggleSection('all_subfolders')}
                      >
                        <div className="w-5 h-5 flex items-center justify-center rounded-md bg-white/50 group-hover:bg-indigo-100 text-zinc-500 group-hover:text-indigo-600 transition-colors shadow-sm border border-zinc-200">
                          {collapsedSections['all_subfolders'] ? (
                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M9 5l7 7-7 7"></path></svg>
                          ) : (
                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M19 9l-7 7-7-7"></path></svg>
                          )}
                        </div>
                        <h3 className="text-xs font-bold uppercase tracking-wider text-zinc-400 group-hover:text-indigo-600 transition-colors m-0">📁 AI Highlight Sub-Folders ({Object.keys(subFoldersMap).length})</h3>
                      </div>

                      {!collapsedSections['all_subfolders'] && (
                        <div className="space-y-4">
                          {Object.keys(subFoldersMap).map((folderName, i) => {
                            const isCurrentSubCollapsed = collapsedSections[`sub_${folderName}`];
                            return (
                              <div key={i} className={`bg-white/40 backdrop-blur-sm border border-white rounded-[2rem] shadow-sm transition-all duration-300 ${isCurrentSubCollapsed ? 'p-3.5' : 'p-5'}`}>
                                
                                <div className={`flex items-center justify-between border-zinc-300/40 ${isCurrentSubCollapsed ? 'pb-0 border-b-0' : 'pb-3 mb-4 border-b'}`}>
                                  <div 
                                    className="flex items-center gap-2 cursor-pointer group flex-1 select-none"
                                    onClick={() => toggleSection(`sub_${folderName}`)}
                                  >
                                    <div className="w-5 h-5 flex items-center justify-center rounded-md bg-white/50 group-hover:bg-indigo-100 text-zinc-500 group-hover:text-indigo-600 transition-colors shadow-sm border border-zinc-200">
                                      {isCurrentSubCollapsed ? (
                                        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M9 5l7 7-7 7"></path></svg>
                                      ) : (
                                        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M19 9l-7 7-7-7"></path></svg>
                                      )}
                                    </div>
                                    <span className="text-xl">📂</span>
                                    <h4 className="text-sm font-black text-zinc-900 tracking-tight group-hover:text-indigo-600 transition-colors">{folderName}</h4>
                                    <span className="text-[10px] bg-indigo-600 text-white font-bold px-2 py-0.5 rounded-full shadow-sm">{subFoldersMap[folderName].length} Klip</span>
                                  </div>

                                  <div className="flex gap-2">
                                    <button onClick={() => handleRenameFolder(folderName)} className="text-[10px] font-bold bg-white border px-2.5 py-1 rounded-lg text-zinc-600 hover:bg-zinc-50 shadow-sm">✏️ Rename</button>
                                    <button onClick={() => handleDeleteSubFolder(folderName)} className="text-[10px] font-bold bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg text-red-700 hover:bg-red-100 shadow-sm">🗑️ Delete</button>
                                  </div>
                                </div>

                                {!isCurrentSubCollapsed && (
                                  <div className="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
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
                                            <button onClick={() => handleDeleteClip(clip.id)} className="text-[9px] text-red-600 font-bold hover:underline">❌ Buang Klip</button>
                                          </div>
                                          <p className="text-[11px] text-zinc-600 font-medium leading-relaxed bg-white/50 p-2.5 rounded-lg border border-white/40">{clip.description}</p>
                                        </div>
                                        <button onClick={() => exportEDL(clip.video, clip.timestamp)} className="w-full bg-white hover:bg-zinc-50 border text-zinc-800 font-bold text-[10px] py-2 mt-3 rounded-lg shadow-sm">Export EDL (Premiere)</button>
                                      </div>
                                    ))}
                                  </div>
                                )}

                              </div>
                            );
                          })}
                        </div>
                      )}
                    </div>
                  )}
                </>
              )}
            </main>

            <aside className="w-96 flex-shrink-0 flex flex-col h-full p-6 pl-0 z-20">
              <div className="bg-white/40 backdrop-blur-md border border-white rounded-[2rem] p-4 flex flex-col h-full shadow-sm relative">
                
                <div className="shrink-0 border-b border-zinc-300/40 pb-2 mb-3 flex justify-between items-center">
                  <div>
                    <h3 className="text-xs font-black text-zinc-900 tracking-tight uppercase flex items-center gap-1.5">⚡ Synkora AI Assistant</h3>
                    <p className="text-[9px] text-zinc-400 font-medium font-mono">Real-time continuous prompt session</p>
                  </div>
                  <button 
                    onClick={handleClearChat} 
                    className="p-1.5 bg-white border border-zinc-200 rounded-lg text-zinc-500 hover:text-red-600 hover:bg-red-50 transition-colors shadow-sm" 
                    title="Mulai Obrolan Baru (Hapus Histori)"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                  </button>
                </div>

                <div className="flex-1 overflow-y-auto space-y-4 mb-4 pr-1">
                  {localMessages.map((msg, i) => (
                    <div key={i} className={`flex flex-col ${msg.sender === 'user' ? 'items-end' : 'items-start'}`}>
                      {/* 🔥 FITUR BARU: Ikon EDIT di sebelah nama user */}
                      <div className="flex items-center gap-2 mb-0.5">
                        {msg.sender === 'user' && (
                          <button 
                            onClick={() => handleEditPrompt(msg.message)} 
                            className="text-[10px] text-zinc-400 hover:text-indigo-600 transition-colors" 
                            title="Edit & Kirim Ulang Prompt"
                          >
                            ✏️ Edit
                          </button>
                        )}
                        <span className="text-[9px] font-bold text-zinc-400 font-mono uppercase">{msg.sender === 'user' ? 'Santa (You)' : 'Synkora Core'}</span>
                      </div>
                      <div className={`max-w-[90%] whitespace-pre-wrap rounded-2xl px-4 py-2.5 text-xs font-medium leading-relaxed shadow-sm ${msg.sender === 'user' ? 'bg-zinc-950 text-white rounded-tr-none' : 'bg-white text-zinc-800 border rounded-tl-none'}`}>
                        {msg.message}
                      </div>
                    </div>
                  ))}

                  {isAiThinking && (
                    <div className="flex flex-col items-start animate-pulse">
                      <span className="text-[9px] font-bold text-indigo-600 font-mono mb-0.5 uppercase">Synkora Core (Thinking)</span>
                      <div className="max-w-[90%] bg-white text-zinc-800 border rounded-2xl rounded-tl-none p-4 text-xs font-medium shadow-md space-y-2.5">
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

                <form onSubmit={handleChatSubmit} className="shrink-0">
                  <div className="flex gap-2 bg-[#F0EEE6] rounded-xl p-1.5 border border-white shadow-inner">
                    <input 
                      type="text" 
                      value={promptText}
                      onChange={(e) => setPromptText(e.target.value)}
                      placeholder={isAiThinking ? "AI sedang bekerja memproses..." : "Tanya / suruh AI disini..."} 
                      disabled={isAiThinking}
                      className="flex-1 bg-transparent border-none text-xs px-2 py-2 focus:outline-none placeholder-zinc-400 text-zinc-900 font-medium disabled:cursor-not-allowed"
                    />
                    
                    {/* 🔥 FITUR BARU: Tombol STOP MERAH saat AI sedang jalan */}
                    {isAiThinking ? (
                      <button 
                        type="button" 
                        onClick={handleStopPrompt}
                        className="px-4 rounded-lg text-xs font-bold transition-colors shadow-sm flex items-center justify-center bg-red-600 hover:bg-red-700 text-white"
                        title="Batalkan Proses"
                      >
                        ⏹ Stop
                      </button>
                    ) : (
                      <button 
                        type="submit" 
                        disabled={!promptText.trim()}
                        className={`px-4 rounded-lg text-xs font-bold transition-colors shadow-sm flex items-center justify-center ${!promptText.trim() ? 'bg-zinc-300 text-zinc-400 cursor-not-allowed' : 'bg-zinc-950 text-white hover:bg-indigo-600'}`}
                      >
                        Send
                      </button>
                    )}
                  </div>
                </form>
              </div>
            </aside>
          </>
        )}
      </div>
    </div>
  );
}