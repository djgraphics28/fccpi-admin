<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FCCPI Youth Joint Fellowship</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-sans">

    <!-- Background Image -->
    <div class="relative w-full h-screen overflow-hidden">
        <img src="{{ asset('565925322_1486175072663983_3141638337069981594_n.jpg') }}"
             alt="FCCPI Youth Joint Fellowship"
             class="absolute inset-0 w-full h-full object-cover opacity-80">

        <!-- Overlay Content -->
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-4">

            <h1 class="text-4xl md:text-6xl font-bold text-cyan-400 mb-4">FCCPI YOUTH JOINT FELLOWSHIP</h1>

            <div class="bg-black bg-opacity-50 rounded-xl p-6 md:p-12 mb-6">
                <h2 class="text-5xl md:text-7xl font-extrabold mb-2">OFF <span class="text-cyan-400">the</span> BOAT</h2>
                <p class="text-lg md:text-2xl font-semibold mb-4">Matthew 14:29</p>

                <div class="space-y-2 md:space-y-4">
                    <p class="text-xl md:text-2xl"><span class="text-cyan-400">Date:</span> 10.31.2025</p>
                    <p class="text-xl md:text-2xl"><span class="text-cyan-400">Time:</span> 9:00 AM - 3:00 PM</p>
                    <p class="text-xl md:text-2xl"><span class="text-cyan-400">Venue:</span> Alcala Garden Resort, San Pedro III, Alcala, Pangasinan</p>
                    <p class="text-xl md:text-2xl"><span class="text-cyan-400">Registration Fee:</span> ₱30.00</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col md:flex-row gap-4 mt-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="px-6 py-3 bg-cyan-400 text-gray-900 font-semibold rounded-lg hover:bg-cyan-500 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-6 py-3 bg-transparent border-2 border-cyan-400 text-cyan-400 font-semibold rounded-lg hover:bg-cyan-400 hover:text-gray-900 transition">
                            Log In
                        </a>
                        <a href="https://fccpi.djnetsolutions.org"
                               class="px-6 py-3 bg-cyan-400 text-gray-900 font-semibold rounded-lg hover:bg-cyan-500 transition">
                                Register
                            </a>
                    @endauth
                @endif
            </div>
        </div>

        <!-- Optional Footer -->
        <footer class="absolute bottom-4 w-full text-center text-gray-300 text-sm">
            &copy; {{ date('Y') }} FCCPI Youth Joint Fellowship. All Rights Reserved.
        </footer>
    </div>

</body>
</html>
