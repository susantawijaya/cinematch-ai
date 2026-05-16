import React, { useEffect, useRef, useCallback } from 'react';

// --- BACKGROUND: INTERACTIVE DOTS ---
const InteractiveDots = ({
  backgroundColor = 'transparent',
  dotColor = '#666666',
  gridSpacing = 35,
  animationSpeed = 0.005,
}) => {
  const canvasRef = useRef(null);
  const timeRef = useRef(0);
  const animationFrameId = useRef(null);
  const mouseRef = useRef({ x: 0, y: 0, isDown: false });
  const ripples = useRef([]);
  const dotsRef = useRef([]);

  const getMouseInfluence = (x, y) => {
    const dx = x - mouseRef.current.x;
    const dy = y - mouseRef.current.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    return Math.max(0, 1 - distance / 150);
  };

  const getRippleInfluence = (x, y, currentTime) => {
    let totalInfluence = 0;
    ripples.current.forEach((ripple) => {
      const age = currentTime - ripple.time;
      if (age < 3000) {
        const dx = x - ripple.x;
        const dy = y - ripple.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        const rippleRadius = (age / 3000) * 300;
        if (Math.abs(distance - rippleRadius) < 60) {
          totalInfluence += (1 - age / 3000) * ripple.intensity * (1 - Math.abs(distance - rippleRadius) / 60);
        }
      }
    });
    return Math.min(totalInfluence, 2);
  };

  const initializeDots = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const dots = [];
    for (let x = gridSpacing / 2; x < canvas.clientWidth; x += gridSpacing) {
      for (let y = gridSpacing / 2; y < canvas.clientHeight; y += gridSpacing) {
        dots.push({ x, y, originalX: x, originalY: y, phase: Math.random() * Math.PI * 2 });
      }
    }
    dotsRef.current = dots;
  }, [gridSpacing]);

  const resizeCanvas = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const dpr = window.devicePixelRatio || 1;
    canvas.width = window.innerWidth * dpr;
    canvas.height = window.innerHeight * dpr;
    canvas.style.width = window.innerWidth + 'px';
    canvas.style.height = window.innerHeight + 'px';
    canvas.getContext('2d')?.scale(dpr, dpr);
    initializeDots();
  }, [initializeDots]);

  const animate = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    timeRef.current += animationSpeed;
    const currentTime = Date.now();
    
    ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);

    dotsRef.current.forEach((dot) => {
      const influence = getMouseInfluence(dot.originalX, dot.originalY) + getRippleInfluence(dot.originalX, dot.originalY, currentTime);
      const dotSize = 1.5 + influence * 5 + Math.sin(timeRef.current + dot.phase) * 0.5;
      const opacity = Math.max(0.12, 0.25 + influence * 0.4);
      
      ctx.beginPath();
      ctx.arc(dot.x, dot.y, dotSize, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(99, 102, 241, ${opacity})`;
      ctx.fill();
    });
    animationFrameId.current = requestAnimationFrame(animate);
  }, [animationSpeed]);

  useEffect(() => {
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    canvasRef.current?.addEventListener('mousemove', (e) => {
      const rect = canvasRef.current.getBoundingClientRect();
      mouseRef.current = { x: e.clientX - rect.left, y: e.clientY - rect.top };
    });
    animate();
    return () => { window.removeEventListener('resize', resizeCanvas); cancelAnimationFrame(animationFrameId.current); };
  }, [animate, resizeCanvas]);

  return <canvas ref={canvasRef} className="absolute inset-0 block w-full h-full opacity-40 z-0 pointer-events-auto" />;
};

// --- MARQUEE TEXT ---
const MarqueeText = () => {
  return (
    <div className="relative flex overflow-x-hidden border-y border-zinc-300/60 bg-white/40 backdrop-blur-sm py-5 z-10 my-12">
      <div className="animate-marquee whitespace-nowrap flex items-center">
        {[...Array(4)].map((_, i) => (
          <span key={i} className="text-2xl font-black tracking-tighter uppercase mx-12 text-zinc-800">
            ✦ BUILT AND DEVELOPED BY SUSANTA WIJAYA
          </span>
        ))}
      </div>
      <div className="absolute top-0 animate-marquee2 whitespace-nowrap flex items-center py-5">
        {[...Array(4)].map((_, i) => (
          <span key={i} className="text-2xl font-black tracking-tighter uppercase mx-12 text-zinc-800">
            ✦ BUILT AND DEVELOPED BY SUSANTA WIJAYA
          </span>
        ))}
      </div>
    </div>
  );
};

// --- MAIN LANDING PAGE COMPONENT ---
export default function LandingPage({ loginUrl }) {
  const scrollTo = (id) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <div className="min-h-screen bg-[#F0EEE6] font-sans overflow-x-hidden relative text-zinc-900 selection:bg-indigo-500/20">
      
      {/* LOVABLE STYLE GRADIENT BLEND */}
      <div className="fixed inset-0 z-0 bg-gradient-to-br from-pink-200/50 via-indigo-100/60 to-teal-100/50 opacity-80 pointer-events-none"></div>
      
      {/* DOTS LAYER */}
      <div className="fixed inset-0 z-0 pointer-events-none">
        <InteractiveDots />
      </div>

      {/* LIQUID GLASS NAVBAR */}
      <nav className="fixed top-0 w-full z-50 bg-white/20 backdrop-blur-xl border-b border-white/40 shadow-sm">
        <div className="max-w-7xl mx-auto px-8 h-20 flex items-center justify-between">
          <div className="flex items-center gap-3 cursor-pointer" onClick={() => scrollTo('hero')}>
            <div className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-md">
              <span className="text-white font-black text-sm">S</span>
            </div>
            <span className="font-bold text-xl tracking-tight text-zinc-900">Synkora</span>
          </div>
          
          <div className="hidden md:flex items-center gap-10 font-semibold text-zinc-600 text-sm">
            <button onClick={() => scrollTo('about')} className="hover:text-indigo-600 transition-colors">About</button>
            <button onClick={() => scrollTo('solutions')} className="hover:text-indigo-600 transition-colors">Solutions</button>
            <button onClick={() => scrollTo('workflow')} className="hover:text-indigo-600 transition-colors">Workflow</button>
          </div>

          <div className="flex items-center gap-4">
            <a href={loginUrl} className="font-bold text-sm text-zinc-700 hover:text-indigo-600 transition-colors hidden md:block">Log In</a>
            <a href={loginUrl} className="bg-zinc-900 text-white px-6 py-2.5 rounded-full font-bold text-sm hover:bg-indigo-600 transition-all shadow-md">
              Get Started
            </a>
          </div>
        </div>
      </nav>

      {/* HERO SECTION */}
      <section id="hero" className="relative z-10 pt-44 pb-24 px-6 flex flex-col items-center justify-center text-center">
        <div className="inline-flex items-center gap-2 bg-white/60 backdrop-blur-md border border-white/80 px-4 py-1.5 rounded-full mb-8 shadow-sm">
          <span className="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Vision AI</span>
          <span className="text-xs font-semibold text-zinc-700">Syncing Memories Instantly ➔</span>
        </div>
        
        <h1 className="text-5xl md:text-8xl font-black tracking-tighter mb-8 leading-[1.05] text-zinc-950">
          Build memories, <br/>
          <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
            Sync with Synkora.
          </span>
        </h1>
        <p className="text-lg md:text-xl text-zinc-600 max-w-2xl mx-auto mb-16 font-medium leading-relaxed">
          Create breathtaking event documentations and social media edits by chatting with your raw footage. Let AI extract the essence of your clips.
        </p>

        {/* NEUMORPHIC PROMPT BOX */}
        <div className="w-full max-w-3xl bg-[#F0EEE6] rounded-[2.5rem] p-4 flex items-center justify-between shadow-[20px_20px_60px_0px_#d9d9d9,-20px_-20px_60px_0px_#ffffff] border border-white/60 transition-transform duration-300 hover:scale-[1.005]">
          <div className="flex items-center gap-4 pl-4 opacity-60">
            <svg className="w-5 h-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 20l4-16m4 4l4 4m-4 10l4-4m-18 4l-4-4m4-4l-4 4" /></svg>
            <span className="text-base font-semibold text-zinc-500">Connect to Google Drive to initialize Synkora...</span>
          </div>
          <a href={loginUrl} className="bg-zinc-900 text-white p-4 rounded-full hover:bg-indigo-600 transition-all shadow-md flex items-center justify-center group">
            <svg className="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
          </a>
        </div>
      </section>

      {/* SCROLLING MARQUEE */}
      <MarqueeText />

      {/* ABOUT SECTION */}
      <section id="about" className="relative z-10 py-24 px-6 max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-16">
        <div className="flex-1">
          <h2 className="text-4xl font-black tracking-tight mb-6 text-zinc-950 leading-tight">Your intelligent<br/>assistant editor.</h2>
          <p className="text-lg text-zinc-600 mb-6 leading-relaxed font-medium">
            Synkora is a deep-learning video studio designed specifically to analyze hours of raw footage, extract the emotional peak moments, and synchronize them automatically to the beat of your track.
          </p>
          <p className="text-lg text-zinc-600 leading-relaxed font-medium">
            By integrating directly with Google Drive, Synkora turns terabytes of disorganized clips from your camera into a ready-to-edit Premiere Pro Timeline or CapCut project in seconds.
          </p>
        </div>
        <div className="flex-1 w-full">
          {/* NEUMORPHIC VIDEO CONTAINER */}
          <div className="aspect-video bg-[#F0EEE6] rounded-[2rem] shadow-[20px_20px_60px_0px_#d9d9d9,-20px_-20px_60px_0px_#ffffff] border border-white flex items-center justify-center overflow-hidden relative">
            <div className="absolute inset-0 bg-gradient-to-tr from-indigo-500/10 to-purple-500/5"></div>
            <div className="text-center z-10 p-6">
              <div className="w-14 h-14 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm border border-zinc-200/50">
                <svg className="w-6 h-6 text-indigo-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /></svg>
              </div>
              <p className="font-bold text-zinc-400 tracking-widest uppercase text-xs">Animation Placeholder</p>
            </div>
          </div>
        </div>
      </section>

      {/* HOW IT WORKS SECTION */}
      <section id="workflow" className="relative z-10 py-24 px-6 max-w-7xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black tracking-tight text-zinc-950 mb-3">Simple 3-Step Workflow</h2>
          <p className="text-zinc-600 font-medium">From chaotic storage folders to cohesive cinematic edits.</p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {[{num: '01', title: 'Link Workspace', desc: 'Securely link your Google Drive folder containing your raw camera footage assets.'},
            {num: '02', title: 'Describe & Analyze', desc: 'Type what you need. AI indexes visual context, audio cues, and action stamps instantly.'},
            {num: '03', title: 'Export Anywhere', desc: 'Download lossless cuts (.mp4) for CapCut Mobile or structural EDL files for Adobe Premiere Pro.'}
          ].map((step, idx) => (
            <div key={idx} className="bg-[#F0EEE6] p-8 rounded-[2rem] shadow-[20px_20px_60px_0px_#d9d9d9,-20px_-20px_60px_0px_#ffffff] border border-white/60 relative overflow-hidden">
              <span className="text-6xl font-black text-indigo-600/10 absolute -top-2 -right-2 font-mono">{step.num}</span>
              <h3 className="text-xl font-bold mb-3 text-zinc-900 relative z-10">{step.title}</h3>
              <p className="text-zinc-600 text-sm leading-relaxed relative z-10 font-medium">{step.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* BENTO GRID SOLUTIONS SECTION */}
      <section id="solutions" className="relative z-10 py-24 px-6 max-w-7xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black tracking-tight text-zinc-950 mb-3">Tailored for Creators</h2>
          <p className="text-zinc-600 font-medium">Synkora scales down hours of editing work into simple creative decisions.</p>
        </div>

        {/* BENTO LAYOUT */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 auto-rows-[220px]">
          {/* Big Card 1 */}
          <div className="md:col-span-2 md:row-span-2 bg-[#F0EEE6] p-10 rounded-[2.5rem] shadow-[20px_20px_60px_0px_#d9d9d9,-20px_-20px_60px_0px_#ffffff] border border-white/60 flex flex-col justify-between">
            <div className="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-zinc-200">
              <svg className="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <div>
              <h3 className="text-2xl font-black mb-3 text-zinc-950">Event Documentation & Public Relations</h3>
              <p className="text-zinc-600 text-base leading-relaxed font-medium max-w-xl">
                The ultimate companion for PDD teams. Instantly sort through hundreds of clips from multiple camera cards. Isolate highlights, speaker frames, and energetic crowd reactions effortlessly.
              </p>
            </div>
          </div>

          {/* Small Card 1 */}
          <div className="bg-[#F0EEE6] p-8 rounded-[2.5rem] shadow-[20px_20px_60px_0px_#d9d9d9,-20px_-20px_60px_0px_#ffffff] border border-white/60 flex flex-col justify-between">
            <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-zinc-200">
              <svg className="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z" /></svg>
            </div>
            <div>
              <h3 className="text-lg font-bold mb-1 text-zinc-950">Beat-Synced Cuts</h3>
              <p className="text-zinc-500 text-xs leading-relaxed font-medium">Automatic rhythm locking for swift musical pacing.</p>
            </div>
          </div>

          {/* Small Card 2 */}
          <div className="bg-[#F0EEE6] p-8 rounded-[2.5rem] shadow-[20px_20px_60px_0px_#d9d9d9,-20px_-20px_60px_0px_#ffffff] border border-white/60 flex flex-col justify-between">
            <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-zinc-200">
              <svg className="w-5 h-5 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </div>
            <div>
              <h3 className="text-lg font-bold mb-1 text-zinc-950">Mobile-Ready</h3>
              <p className="text-zinc-500 text-xs leading-relaxed font-medium">Lossless renders optimized natively for CapCut or Reels.</p>
            </div>
          </div>

          {/* Wide Card */}
          <div className="md:col-span-3 bg-[#F0EEE6] p-8 rounded-[2.5rem] shadow-[20px_20px_60px_0px_#d9d9d9,-20px_-20px_60px_0px_#ffffff] border border-white/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div className="max-w-xl">
              <h3 className="text-xl font-bold mb-2 text-zinc-950">Sports & League Highlights</h3>
              <p className="text-zinc-600 text-sm leading-relaxed font-medium">
                Track specific key plays, game numbers, or target actions (e.g., 3-point shots, MVP smiles) straight out of the camera index.
              </p>
            </div>
            <a href={loginUrl} className="bg-zinc-900 text-white px-6 py-3 rounded-full font-bold text-sm hover:bg-indigo-600 transition-all shrink-0 shadow-md">
              Try Workspace Isolation
            </a>
          </div>
        </div>
      </section>

      {/* FOOTER */}
      <footer className="relative z-10 border-t border-zinc-300/40 bg-white/10 backdrop-blur-md pt-16 pb-8 px-6 text-center">
        <p className="font-bold text-zinc-800 text-lg mb-1">Synkora Studio</p>
        <p className="text-zinc-500 text-xs font-semibold mb-8">Intelligent Compilations. Zero Manual Scrubbing.</p>
        <div className="text-[11px] font-bold text-zinc-400 uppercase tracking-widest flex justify-center gap-8">
          <a href="#" className="hover:text-zinc-900 transition-colors">Privacy</a>
          <a href="#" className="hover:text-zinc-900 transition-colors">Terms</a>
          <a href="#" className="hover:text-zinc-900 transition-colors">Contact</a>
        </div>
      </footer>

    </div>
  );
}