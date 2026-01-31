<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Sedang Istirahat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap');

        body {
            font-family: 'Nunito', sans-serif;
        }

        .float-anim {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">
    <div
        class="bg-white rounded-3xl shadow-2xl p-8 max-w-lg w-full text-center border-4 border-indigo-100 transform transition hover:scale-105 duration-500">

        <div class="mb-6 flex justify-center">
            <!-- Frieren Tea GIF -->
            <img src="https://media.giphy.com/media/jUckyQVjuHNx9vXUtv/giphy.gif"
                 alt="Frieren Tea"
                 class="rounded-xl shadow-md float-anim border-2 border-indigo-50 w-64 h-56 object-cover object-top">
        </div>

        <h1 class="text-4xl font-extrabold text-indigo-600 mb-2">Aplikasi Maintenance</h1>
        <p class="text-gray-500 text-lg mb-6">"Tunggu sebentar, sedang direkap."</p>

        <div class="bg-indigo-50 rounded-xl p-4 mb-6">
            <p class="text-sm text-indigo-700 font-semibold">
                ⚠️ Akses Ditutup Sementara oleh Admin
            </p>
        </div>

        <div class="space-y-2">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 w-1/3 animate-pulse"></div>
            </div>
            <p class="text-xs text-gray-400">Silakan kembali lagi nanti ya!</p>
        </div>
    </div>
</body>

</html>