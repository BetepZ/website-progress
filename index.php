<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Tugas Video & Artikel</title>
    <!-- Memuat Tailwind CSS dari CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Memuat Alpine.js dari CDN (PENTING: Atribut defer wajib ada) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <!-- Phosphor Icons untuk ikon-ikon cantik -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* Mencegah layar berkedip saat Alpine.js sedang dimuat */
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 antialiased min-h-screen">

    <!-- Komponen Utama Alpine.js -->
    <div x-data="taskManager()" x-init="initData()" x-cloak class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">

        <!-- Notifikasi Error (Akan muncul jika gagal koneksi ke API backend) -->
        <div x-show="errorMessage"
            class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg relative flex items-center gap-3 shadow-sm"
            style="display: none;">
            <i class="ph ph-warning-circle text-2xl"></i>
            <div>
                <strong class="font-bold block">Terjadi Kesalahan!</strong>
                <span class="block text-sm" x-text="errorMessage"></span>
            </div>
        </div>

        <!-- HEADER & PROGRESS BAR UTAMA -->
        <header class="mb-10 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Progress Tugas</h1>
                    <p class="text-gray-500 mt-1">Target: 13 Minggu (156 Total Tugas)</p>
                </div>
                <div class="mt-4 md:mt-0 text-right">
                    <div class="text-4xl font-extrabold text-indigo-600" x-text="`${globalProgress}%`"></div>
                    <div class="text-sm text-gray-400 font-medium mt-1">Selesai: <span x-text="completedCount"></span> /
                        156</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden shadow-inner">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-full rounded-full transition-all duration-700 ease-in-out"
                    :style="`width: ${globalProgress}%`">
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <!-- KOLOM KIRI: CONTRIBUTION GRID (156 Balok) -->
            <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Peta Kontribusi</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-500 font-medium">
                        <span class="flex items-center gap-1">
                            <div class="w-3 h-3 bg-green-500 rounded-sm"></div> Selesai
                        </span>
                        <span class="flex items-center gap-1">
                            <div class="w-3 h-3 bg-blue-500 rounded-sm"></div> Penting
                        </span>
                    </div>
                </div>

                <!-- Kontainer Grid (12 kolom per baris) -->
                <div class="grid grid-cols-12 gap-1.5 sm:gap-2">
                    <template x-for="task in allTasks" :key="task.id">
                        <div @click="toggleImportantGrid(task)"
                            class="w-full aspect-square rounded-sm cursor-pointer transition-colors duration-200 shadow-sm border border-black/5 hover:scale-110 transform"
                            :class="{
                                'bg-green-500 border-green-600': hasUrl(task), 
                                'bg-blue-500 border-blue-600': !hasUrl(task) && task.is_important == 1,
                                'bg-gray-100 hover:bg-gray-300': !hasUrl(task) && task.is_important != 1
                            }"
                            :title="`Minggu ${task.week_number} - ${capitalize(task.category)} ${task.slot_number}`">
                        </div>
                    </template>
                </div>
            </div>

            <!-- KOLOM KANAN: MANAJEMEN MINGGUAN -->
            <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

                <!-- Kontrol Navigasi Minggu -->
                <div
                    class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b border-gray-100 gap-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="ph ph-calendar-check text-indigo-500 text-2xl"></i>
                        Manajemen Form
                    </h2>

                    <select x-model="selectedWeek" @change="loadWeekData"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:w-48 p-2.5 outline-none font-medium">
                        <template x-for="i in 13">
                            <option :value="i" x-text="`Target Minggu Ke-${i}`"></option>
                        </template>
                    </select>
                </div>

                <!-- Indikator Loading -->
                <div x-show="isLoadingWeek" class="flex justify-center items-center py-12">
                    <i class="ph ph-spinner animate-spin text-4xl text-indigo-500"></i>
                </div>

                <!-- Daftar Card Tugas per Minggu -->
                <div x-show="!isLoadingWeek" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="task in currentWeekTasks" :key="task.id">

                        <!-- Card Individual Tugas -->
                        <div class="border rounded-xl p-4 transition-all duration-300 relative overflow-hidden group"
                            :class="hasUrl(task) ? 'bg-green-50/50 border-green-200' : (task.is_important == 1 ? 'bg-blue-50/50 border-blue-200' : 'bg-white border-gray-200 hover:border-indigo-300')">

                            <!-- Header Card: Kategori & Toggle Penting -->
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-sm font-bold uppercase tracking-wider flex items-center gap-1.5"
                                    :class="{
                                          'text-rose-600': task.category === 'instagram',
                                          'text-red-600': task.category === 'youtube',
                                          'text-emerald-600': task.category === 'article'
                                      }">
                                    <i class="text-lg" :class="{
                                        'ph-fill ph-instagram-logo': task.category === 'instagram',
                                        'ph-fill ph-youtube-logo': task.category === 'youtube',
                                        'ph-fill ph-article': task.category === 'article'
                                    }"></i>
                                    <span x-text="`${task.category} ${task.slot_number}`"></span>
                                </span>

                                <!-- Tombol Toggle Penting -->
                                <button @click="toggleImportant(task)"
                                    class="text-gray-400 hover:text-blue-600 transition-colors focus:outline-none"
                                    :class="{'text-blue-600': task.is_important == 1}">
                                    <i class="text-xl"
                                        :class="task.is_important == 1 ? 'ph-fill ph-star' : 'ph ph-star'"></i>
                                </button>
                            </div>

                            <!-- Form Input URL -->
                            <div class="relative">
                                <input type="url" x-model="task.tempUrl" @blur="saveUrl(task)"
                                    @keydown.enter="$event.target.blur()" placeholder="Tempel tautan di sini..."
                                    class="bg-gray-50 border text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 pr-10 outline-none transition-colors"
                                    :class="hasUrl(task) ? 'border-green-300' : 'border-gray-300'">

                                <!-- Ikon Sukses Save -->
                                <div x-show="task.isSaving" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <i class="ph ph-spinner animate-spin text-gray-500"></i>
                                </div>
                                <div x-show="!task.isSaving && task.url === task.tempUrl && hasUrl(task)"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <i class="ph-fill ph-check-circle text-green-500 text-lg"></i>
                                </div>
                            </div>

                            <!-- Preview / Tombol Aksi External -->
                            <div class="mt-3 h-10 flex items-end">
                                <!-- Jika URL YouTube (Tampilkan Thumbnail) -->
                                <template x-if="hasUrl(task) && isYouTube(task.url)">
                                    <a :href="task.url" target="_blank"
                                        class="block w-full overflow-hidden rounded-lg border border-gray-200 hover:opacity-90 transition-opacity">
                                        <div class="h-24 w-full bg-cover bg-center"
                                            :style="`background-image: url('${getYTThumbnail(task.url)}')`"></div>
                                        <div
                                            class="bg-white/90 text-xs text-center py-1 font-medium text-gray-700 flex justify-center items-center gap-1">
                                            <i class="ph-fill ph-play-circle text-red-500"></i> Tonton Video
                                        </div>
                                    </a>
                                </template>

                                <!-- Jika URL Instagram/Artikel (Tombol Sederhana) -->
                                <template x-if="hasUrl(task) && !isYouTube(task.url)">
                                    <a :href="task.url" target="_blank"
                                        class="w-full inline-flex justify-center items-center gap-2 py-2 px-4 text-sm font-medium text-white rounded-lg transition-all focus:outline-none focus:ring-4"
                                        :class="{
                                           'bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 focus:ring-pink-200': task.category === 'instagram',
                                           'bg-emerald-500 hover:bg-emerald-600 focus:ring-emerald-200': task.category === 'article'
                                       }">
                                        Buka Tautan <i class="ph ph-arrow-square-out text-lg"></i>
                                    </a>
                                </template>
                            </div>

                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('taskManager', () => ({
                allTasks: [],
                currentWeekTasks: [],
                selectedWeek: 1,
                globalProgress: 0,
                completedCount: 0,
                isLoadingWeek: false,
                errorMessage: '',

                async initData() {
                    this.errorMessage = '';
                    await this.loadDashboardGrid();
                    await this.loadWeekData();
                },

                // Mengecek apakah URL valid dan tidak hanya spasi kosong
                hasUrl(task) {
                    return task.url && task.url.trim() !== '';
                },

                async loadDashboardGrid() {
                    try {
                        const response = await fetch('api/dashboard.php');
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const result = await response.json();

                        if (result.status === 'success') {
                            this.allTasks = result.data;
                            this.calculateProgress();
                            this.errorMessage = '';
                        } else {
                            throw new Error(result.message || 'Respons API tidak valid.');
                        }
                    } catch (error) {
                        console.error("Gagal memuat Grid:", error);
                        this.errorMessage =
                            "Gagal mengambil data dari server (dashboard.php). Pastikan API PHP dapat diakses.";
                    }
                },

                calculateProgress() {
                    this.completedCount = this.allTasks.filter(t => this.hasUrl(t)).length;
                    const totalTasks = 156;
                    this.globalProgress = Math.round((this.completedCount / totalTasks) * 100);
                },

                async loadWeekData() {
                    this.isLoadingWeek = true;
                    try {
                        const response = await fetch(`api/week.php?w=${this.selectedWeek}`);
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const result = await response.json();

                        if (result.status === 'success') {
                            this.currentWeekTasks = result.data.map(t => ({
                                ...t,
                                tempUrl: t.url || '',
                                isSaving: false
                            }));
                        } else {
                            throw new Error(result.message || 'Respons API tidak valid.');
                        }
                    } catch (error) {
                        console.error("Gagal memuat form mingguan:", error);
                        this.errorMessage =
                            "Gagal mengambil data form mingguan. Cek koneksi atau file week.php.";
                    } finally {
                        this.isLoadingWeek = false;
                    }
                },

                // UTILITY API TERPUSAT
                async updateTaskAPI(id, url, is_important) {
                    const response = await fetch('api/update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id,
                            url,
                            is_important
                        })
                    });
                    const result = await response.json();
                    if (result.status !== 'success') throw new Error(result.message);
                },

                // KHUSUS UNTUK MENYIMPAN URL BARU DARI FORM
                async saveUrl(task) {
                    // Hanya save jika link benar-benar berubah dari sebelumnya
                    if (task.url === task.tempUrl) return;

                    task.isSaving = true;
                    try {
                        await this.updateTaskAPI(task.id, task.tempUrl, task.is_important);
                        task.url = task.tempUrl; // Simpan ke state lokal jika API sukses
                        await this.loadDashboardGrid(); // Refresh grid agar hijau
                    } catch (error) {
                        console.error("Gagal menyimpan URL:", error);
                        alert("Gagal menyimpan URL: " + error.message);
                        task.tempUrl = task.url; // Kembalikan ke teks lama
                    } finally {
                        task.isSaving = false;
                    }
                },

                // KHUSUS UNTUK KLIK BINTANG DARI FORM
                async toggleImportant(task) {
                    const newState = (task.is_important == 1) ? 0 : 1;
                    task.is_important = newState; // Efek UI langsung biru/abu

                    try {
                        await this.updateTaskAPI(task.id, task.url, newState);
                        await this.loadDashboardGrid(); // Sinkronisasi grid 
                    } catch (error) {
                        task.is_important = (newState == 1) ? 0 : 1; // Rollback jika error
                        console.error(error);
                    }
                },

                // KHUSUS UNTUK KLIK KOTAK DI GRID DASHBOARD KIRI
                async toggleImportantGrid(task) {
                    // Blokir perubahan jika tugas sudah selesai (hijau tidak bisa jadi biru)
                    if (this.hasUrl(task)) return;

                    const newState = (task.is_important == 1) ? 0 : 1;
                    task.is_important = newState; // Optimistic UI

                    // Sinkronisasi ke form kanan (jika minggu yang tampil sama dengan kotak yang diklik)
                    const formTask = this.currentWeekTasks.find(t => t.id === task.id);
                    if (formTask) formTask.is_important = newState;

                    try {
                        await this.updateTaskAPI(task.id, task.url, newState);
                    } catch (error) {
                        task.is_important = (newState == 1) ? 0 : 1; // Rollback grid
                        if (formTask) formTask.is_important = task.is_important; // Rollback form
                        console.error("Gagal toggle status penting:", error);
                    }
                },

                capitalize(str) {
                    return str.charAt(0).toUpperCase() + str.slice(1);
                },

                isYouTube(url) {
                    return url && (url.includes('youtube.com') || url.includes('youtu.be'));
                },

                getYTThumbnail(url) {
                    let videoId = '';
                    try {
                        if (url.includes('youtu.be/')) {
                            videoId = url.split('youtu.be/')[1].split('?')[0];
                        } else if (url.includes('youtube.com/watch')) {
                            const urlObj = new URL(url);
                            videoId = urlObj.searchParams.get('v');
                        }
                    } catch (e) {}

                    return videoId ? `https://img.youtube.com/vi/${videoId}/mqdefault.jpg` : '';
                }
            }));
        });
    </script>
</body>

</html>