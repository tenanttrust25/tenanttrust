<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Site - Tenant Trust</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-lg w-full mx-4">
        <h1 class="text-2xl font-bold text-center mb-2">Upload Your Site</h1>
        <p class="text-gray-500 text-center mb-6">Upload a .tar.gz file to deploy as the Tenant Trust website</p>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4">{{ session('error') }}</div>
        @endif

        <form action="/upload" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition cursor-pointer" onclick="document.getElementById('file').click()">
                <div class="text-4xl mb-2">📦</div>
                <p class="text-gray-500" id="fileLabel">Click to select .tar.gz file</p>
                <input type="file" id="file" name="site_archive" accept=".tar.gz,.tgz" class="hidden" onchange="document.getElementById('fileLabel').textContent = this.files[0].name">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                Upload & Deploy
            </button>
        </form>

        <p class="text-xs text-gray-400 text-center mt-4">
            The archive will be extracted to the site root. Existing files with the same names will be overwritten.
        </p>
    </div>
</body>
</html>
