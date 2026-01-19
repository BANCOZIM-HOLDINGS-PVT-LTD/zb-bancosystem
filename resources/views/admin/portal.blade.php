<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BancoSystem Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #f6f8fb 0%, #e2e8f0 100%);
            background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,0) 0, hsla(253,16%,7%,0) 50%), radial-gradient(at 50% 0%, hsla(225,39%,30%,0) 0, hsla(225,39%,30%,0) 50%), radial-gradient(at 100% 0%, hsla(339,49%,30%,0) 0, hsla(339,49%,30%,0) 50%);
        }
        .animated-bg {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-gray-50 relative overflow-x-hidden">
    <div class="animated-bg"></div>
    
    <div class="max-w-7xl w-full relative z-10">
        <div class="text-center mb-16 flex flex-col items-center">
            <div class="mb-6 p-4 glass rounded-3xl inline-block shadow-lg">
                <img src="/adala.jpg" alt="Adala Logo" class="h-24 w-auto object-contain">
            </div>
            <h1 class="text-5xl font-bold text-gray-900 mb-4 tracking-tight drop-shadow-sm">Admin Ecosystem</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 px-4">
            <!-- ZB Admin -->
            <a href="/zb-admin" class="block group h-full">
                <div class="glass rounded-3xl p-8 h-full card-hover border-t-4 border-green-500 relative overflow-hidden bg-white/60">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-green-500/5 rounded-bl-full -mr-10 -mt-10 transition-all duration-500 group-hover:bg-green-500/10 group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-green-500/30 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-green-600 transition-colors">ZB Bank Admin</h3>
                       
                    </div>
                </div>
            </a>

            <!-- Accounting -->
            <a href="/accounting" class="block group h-full">
                <div class="glass rounded-3xl p-8 h-full card-hover border-t-4 border-blue-500 relative overflow-hidden bg-white/60">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-bl-full -mr-10 -mt-10 transition-all duration-500 group-hover:bg-blue-500/10 group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">Accounting</h3>
                        
                    </div>
                </div>
            </a>

            <!-- Stores -->
            <a href="/stores" class="block group h-full">
                <div class="glass rounded-3xl p-8 h-full card-hover border-t-4 border-orange-500 relative overflow-hidden bg-white/60">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-orange-500/5 rounded-bl-full -mr-10 -mt-10 transition-all duration-500 group-hover:bg-orange-500/10 group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-orange-500/30 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-orange-600 transition-colors">Stores Manager</h3>
                         </div>
                </div>
            </a>

            <!-- HR -->
            <a href="/hr" class="block group h-full">
                <div class="glass rounded-3xl p-8 h-full card-hover border-t-4 border-purple-500 relative overflow-hidden bg-white/60">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-purple-500/5 rounded-bl-full -mr-10 -mt-10 transition-all duration-500 group-hover:bg-purple-500/10 group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-purple-500/30 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-purple-600 transition-colors">HR & Payroll</h3>
                         </div>
                </div>
            </a>

            <!-- Partner read only dashboard -->
            <a href="/partner" class="block group h-full">
                <div class="glass rounded-3xl p-8 h-full card-hover border-t-4 border-gray-600 relative overflow-hidden bg-white/60">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-gray-600/5 rounded-bl-full -mr-10 -mt-10 transition-all duration-500 group-hover:bg-gray-600/10 group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-gray-700 to-gray-900 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-gray-500/30 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-gray-700 transition-colors">Partner Access</h3>
                           </div>
                </div>
            </a>
        </div>
        
        <div class="mt-16 text-center">
            <a href="/login" class="inline-flex items-center px-6 py-3 rounded-full text-sm font-medium text-gray-500 hover:text-gray-900 bg-white/50 hover:bg-white/80 backdrop-blur-sm transition-all shadow-sm hover:shadow-md border border-gray-200">
                <span>Super Admin Access</span>
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
    </div>
</body>
</html>
