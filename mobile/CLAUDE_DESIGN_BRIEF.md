# LMS MOE Mobile — Flutter Design Brief untuk Claude

> Dokumen ini ialah sumber tunggal untuk mereka bentuk aplikasi Flutter LMS MOE. Gunakan dokumen ini sebagai product brief, UX brief, visual direction, information architecture, dan senarai skrin.
>
> **Penting:** reka bentuk baharu mesti mengambil DNA visual daripada web LMS sedia ada, tetapi **tidak boleh menjadi salinan kecil halaman web**. Hasil akhir perlu terasa seperti aplikasi Android/iOS/tablet yang moden, pantas, mesra murid sekolah rendah, dan tetap profesional untuk guru.

---

## 1. Ringkasan produk

**LMS MOE** ialah Learning Management System untuk sekolah rendah Malaysia. Ia membolehkan murid belajar melalui video, bahan pembelajaran dan kuiz; guru pula mengurus kandungan serta melihat penglibatan dan hasil murid.

Struktur pembelajaran utama ialah:

```text
Tahun 1 hingga Tahun 6
  └── Subjek
       └── Bab
            ├── Video / Pelajaran
            ├── Bahan pembelajaran (PDF, dokumen, slaid, imej)
            └── Kuiz
```

Platform asal ialah web Laravel. Aplikasi Flutter ialah pengalaman mudah alih baharu untuk dua peranan utama:

1. **Murid** — mencari dan menggunakan kandungan pembelajaran.
2. **Guru** — memuat naik, mengurus dan menilai kandungan/pencapaian murid.

Peranan admin KPM/MOE wujud dalam sistem web sebagai dashboard pengawasan, tetapi **tidak perlu direka untuk aplikasi mudah alih pada fasa pertama**.

---

## 2. Matlamat reka bentuk Flutter

Reka bentuk mesti mencapai matlamat ini:

- Rasa seperti aplikasi pendidikan mobile-native, bukan portal web yang dikecilkan.
- Murid Tahun 1–6 boleh faham navigasi tanpa perlu membaca arahan panjang.
- Guru boleh mengurus kandungan dengan cepat, termasuk pada tablet.
- Menyokong telefon kecil, telefon biasa dan tablet Android/iPad.
- Menggunakan Bahasa Melayu sebagai bahasa utama; bersedia untuk Bahasa Inggeris.
- Menunjukkan maklumat penting tanpa membuat skrin terlalu padat.
- Menjaga aksesibiliti: teks jelas, kontras tinggi, sasaran sentuhan minimum 44–48 px, status tidak bergantung kepada warna sahaja.
- Menggunakan visual yang matang dan mesra kanak-kanak, bukan terlalu kebudak-budakan atau bergaya permainan berlebihan.

### Arah visual baharu

Ambil identiti daripada web semasa, kemudian tambah ciri mobile-native berikut:

- Home murid yang lebih personal, visual dan berasaskan “apa saya patut buat sekarang”.
- Kad kandungan lebih ringkas dengan thumbnail, status kemajuan dan tindakan pantas.
- Bottom navigation untuk telefon; navigation rail / sidebar ringan untuk tablet.
- Bottom sheet untuk penapis, pilihan tahun, pilihan tindakan kandungan dan menu profil.
- Skeleton/loading states, empty states dan error states yang direka dengan baik.
- Guru mendapat interface yang lebih produktif dan lebih padat, tetapi masih mempunyai ruang putih yang cukup.
- Gunakan micro-interactions yang halus sahaja: transition kad, progress animation, success state kuiz. Jangan gunakan animasi berat.

### Jangan buat perkara ini

- Jangan copy susun atur desktop web satu-ke-satu.
- Jangan letak semua fungsi pada satu skrin dashboard.
- Jangan guna gradient ungu/biru generik atau gaya “template edtech” yang tidak berkait dengan LMS MOE.
- Jangan jadikan semua kad berwarna terang; warna mesti membantu hierarki, bukan mengganggu.
- Jangan gunakan logo KPM rasmi atau lambang kerajaan melainkan aset rasmi telah diberi.
- Jangan reka fungsi yang belum wujud sebagai fungsi teras tanpa menandakan ia sebagai “future enhancement”.

---

## 3. Pengguna, konteks dan nada

### 3.1 Murid sekolah rendah

**Umur anggaran:** 7–12 tahun, Tahun 1–6.

**Keperluan:**

- Cari subjek dan bab dengan cepat.
- Tahu video yang perlu disambung.
- Boleh menyimpan video/bahan kegemaran.
- Boleh menjawab kuiz tanpa rasa keliru atau takut.
- Melihat kemajuan dan kedudukan dengan cara yang positif.

**Nada:** pendek, hangat, jelas, menggalakkan.

Contoh:

- “Hai, Aisyah! Jom sambung belajar.”
- “Hebat! Anda sudah menamatkan video ini.”
- “Cuba lagi untuk latihan — markah ranking anda tidak berubah.”

### 3.2 Guru

**Keperluan:**

- Cepat melihat ringkasan kandungan dan aktiviti terkini.
- Menambah atau menyunting video, bahan, kuiz dan bab.
- Memilih `Subjek → Tahun → Bab` tanpa kesilapan.
- Mengetahui status terbit/sembunyi kandungan.
- Melihat statistik kuiz dan penglibatan murid.
- Memahami Skor Bakat secara telus tanpa menganggapnya sebagai markah mutlak.

**Nada:** profesional, terang, tidak terlalu teknikal.

Contoh:

- “Tambah video pembelajaran”
- “Video diterbitkan — murid kini boleh menontonnya.”
- “Data belum mencukupi untuk skor bakat.”

### 3.3 KPM/MOE admin (future web-first)

Admin MOE ialah peranan pengawasan sahaja. Mereka melihat skor bakat guru, menapis mengikut subjek/tahun, melihat pecahan kandungan, dan eksport laporan CSV. Mereka tidak mencipta kandungan.

Untuk fasa Flutter sekarang, cukup sediakan state “Portal MOE tersedia di web” jika akaun admin log masuk. Jangan reka full admin mobile dashboard dahulu.

---

## 4. Identity daripada web semasa

### 4.1 Warna asas

Gunakan palet ini sebagai asas. Boleh tambah warna sokongan yang serasi, tetapi jangan ubah identiti teal utama.

| Token | Hex | Kegunaan |
|---|---:|---|
| Background | `#F7F8FA` | Latar halaman, bukan putih tulen |
| Surface | `#FFFFFF` | Kad, panel, dialog |
| Muted surface | `#F1F3F6` | Input, hover, placeholder, skeleton |
| Ink | `#0F172A` | Tajuk dan teks utama |
| Muted ink | `#5B6675` | Teks sokongan |
| Brand teal | `#0F766E` | CTA utama, progress aktif, active navigation |
| Brand strong | `#115E56` | Pressed/hover state |
| Brand soft | `#E6F5F2` | Badge, ikon container, highlighted card |
| Success | `#15803D` | Siap/diterbitkan/berjaya |
| Warning | `#B45309` | Makluman/perhatian |
| Danger | `#B91C1C` | Padam/ralat |

### 4.2 Bentuk dan depth

Web semasa menggunakan permukaan lembut, border halus dan shadow yang ringan.

| Elemen | Cadangan radius |
|---|---:|
| Input / button / chip | 10 px |
| Kad biasa | 14–16 px |
| Panel atau hero | 20–24 px |
| Avatar | Bulat penuh |
| Bottom sheet | 24–28 px di bahagian atas |

Gunakan shadow yang lembut dan pendek. Kad perlu kelihatan boleh disentuh tetapi tidak terapung terlalu tinggi.

### 4.3 Tipografi

Web menggunakan sans-serif moden dengan tajuk bold dan teks pembacaan yang mesra. Untuk Flutter:

- Gunakan satu sans-serif yang jelas dan baik untuk Bahasa Melayu.
- Tajuk: berat 700–800.
- Label butang dan navigation: berat 600–700.
- Body: 400–500, line height selesa.
- Jangan gunakan teks terlalu kecil; minimum 14 px untuk body penting.
- Skala murid boleh sedikit lebih besar daripada guru.

### 4.4 Ikon dan ilustrasi

- Guna Material Symbols atau ikon outline yang konsisten.
- Ikon perlu dibantu label teks untuk tindakan penting.
- Untuk empty state murid, boleh guna ilustrasi bentuk ringkas / friendly icon scene. Jangan guna karakter kartun yang terlalu spesifik jika aset belum ada.
- Thumbnail video menggunakan gambar kandungan/YouTube apabila tersedia; fallback ialah placeholder subjek berwarna lembut.

---

## 5. Domain model dan peraturan produk

Bahagian ini penting supaya reka bentuk tidak bercanggah dengan sistem sebenar.

### 5.1 Akademik dan kandungan

| Entiti | Maksud |
|---|---|
| Tahun | Tahap murid: Tahun 1 hingga Tahun 6 |
| Subjek | Contoh: Bahasa Melayu, Matematik, Sains |
| Bab | Unit pembelajaran di dalam Subjek + Tahun |
| Video/Pelajaran | Video upload atau pautan YouTube oleh guru |
| Bahan | PDF, dokumen, PowerPoint, imej dan fail sokongan |
| Kuiz | Kuiz fail untuk dimuat turun atau kuiz interaktif dalam aplikasi |

Murid melihat kandungan yang sesuai dengan Tahun mereka. Mereka juga boleh menukar Tahun untuk ulang kaji / preview jika fungsi itu dibenarkan dalam sistem.

### 5.2 Video

Jenis video:

1. **Upload** — MP4/WebM yang dimuat naik oleh guru.
2. **YouTube** — dimainkan dalam aplikasi menggunakan embed/pemain YouTube.

Peraturan pengalaman murid:

- Tontonan unik dikira sekali bagi setiap murid untuk setiap video.
- Kemajuan disimpan secara berkala, ketika pause, tamat dan keluar halaman.
- Video dianggap tamat apabila murid menonton sekurang-kurangnya **90%**.
- Murid boleh sambung dari kedudukan terakhir.
- Murid boleh kegemaran (favourite) video.
- Video upload boleh dimuat turun jika guru/sistem membenarkan; YouTube tidak ditawarkan sebagai download aplikasi.

Untuk UI:

- Papar badge `Sambung`, `% siap`, atau `Selesai`.
- Player screen mesti mempunyai tajuk, subjek/bab, button favourite, bahan sokongan, kuiz berkaitan, serta video sebelum/selepas jika ada.
- Jangan paparkan metrik dalaman seperti view counter kepada murid kecuali ia diperlukan.

### 5.3 Bahan pembelajaran

Bahan boleh berupa PDF, PPT/PPTX, DOC/DOCX, XLS/XLSX atau imej.

UI perlu menunjukkan:

- Ikon jenis fail.
- Nama fail/tajuk bahan.
- Saiz jika data tersedia.
- Button `Buka` atau `Muat turun`.
- Empty state apabila tiada bahan.

### 5.4 Kegemaran, simpanan dan sambung belajar

| Modul | Maksud |
|---|---|
| Kegemaran | Video yang murid tandakan hati untuk kembali kemudian |
| Simpanan | Kandungan/fail untuk akses lebih mudah atau offline (ikut kemampuan platform) |
| Sambung belajar | Video yang telah dimulakan tetapi belum selesai |

Jangan gabungkan ketiga-tiga secara visual kerana maksudnya berbeza. Gunakan status dan empty state yang terang.

### 5.5 Kuiz

Terdapat dua jenis:

1. **Kuiz fail** — murid muat turun bahan/lembaran kuiz, tiada jawapan interaktif dalam aplikasi.
2. **Kuiz interaktif** — soalan pilihan tunggal (radio) atau pilihan pelbagai (checkbox).

Aliran kuiz interaktif:

```text
Intro kuiz
  → baca tajuk, bab, bilangan soalan, masa dan peraturan
  → mula kuiz
  → jawab soalan satu demi satu atau dengan navigator soalan
  → hantar jawapan
  → keputusan: markah, peratus, jawapan betul/salah, ulasan
```

Peraturan:

- Hanya murid boleh memulakan percubaan.
- Percubaan pertama yang tamat dikira ke ranking.
- Percubaan seterusnya ialah latihan: masih boleh melihat markah dan jawapan, tetapi tidak menukar ranking.
- Kuiz bertempoh perlu mempunyai timer yang stabil jika aplikasi direfresh/dibuka semula.
- Soalan multiple choice adalah all-or-nothing jika semua jawapan perlu tepat.

Reka bentuk keputusan kuiz:

- Skor mesti jelas, besar dan mesra.
- Gunakan celebration ringan untuk skor 80% ke atas.
- Jangan memalukan murid untuk skor rendah; galakkan ulang kaji dan cuba semula.
- Papar `Latihan` dengan jelas untuk cubaan yang tidak memberi kesan pada ranking.

### 5.6 Ranking murid

Ranking berdasarkan mata daripada percubaan kuiz pertama yang layak.

Murid dibandingkan dalam Tahun mereka sendiri, bukan seluruh sekolah atau Tahun lain. Ranking boleh ditapis mengikut subjek.

UI perlu:

- Papar top learners secara positif.
- Sentiasa pin kedudukan murid sendiri jika mereka berada di luar Top 10.
- Elakkan visual yang terlalu kompetitif bagi kanak-kanak kecil.
- Gunakan avatar/initial, nama pengguna mesra privasi, mata dan ketepatan.

---

## 6. Modul Murid — senarai skrin dan UX

### 6.1 Authentication

**Skrin:** Splash / session restore / Login

Keperluan:

- Logo teks ringkas: `LMS` badge teal + `MOE`.
- Field `Nama pengguna atau emel`.
- Field `Kata laluan` dengan show/hide.
- CTA `Log Masuk` besar dan mudah disentuh.
- Pautan `Lupa kata laluan?` dan `Daftar` boleh dipaparkan jika disokong pada fasa API.
- Selepas login, aplikasi menerima role daripada backend dan menghala secara automatik:

```text
role = student → Student app shell
role = teacher → Teacher app shell
role = admin   → mesej: Portal MOE tersedia di web
```

**Dalam Flutter preview sekarang:** login lokal digunakan hanya untuk demonstrasi visual. Jangan reka seolah-olah ia authentication production.

### 6.2 Student app shell / navigation

Cadangan mobile phone: **5 tab bottom navigation**

| Tab | Ikon | Kandungan |
|---|---|---|
| Utama | Home | Dashboard murid, sambung belajar, cadangan |
| Teroka | Book/search | Subjek, Tahun, carian, bab |
| Simpan | Bookmark/download | Kegemaran, simpanan/offline |
| Kuiz | Quiz | Kuiz saya, sejarah percubaan |
| Profil | Avatar | Profil, Tahun, bahasa, tema, logout |

Untuk tablet: gunakan navigation rail kiri dan content grid yang lebih lebar. Jangan hanya meregangkan layout telefon.

### 6.3 Student home

**Tujuan:** Jawab soalan “Apa saya patut belajar sekarang?”

Kandungan yang disyorkan:

1. Header: sapaan, avatar, Tahun aktif, notification placeholder jika diperlukan.
2. Hero `Sambung belajar`:
   - thumbnail,
   - subjek + bab,
   - tajuk video,
   - progress bar / %,
   - CTA `Sambung`.
3. `Subjek saya` — horizontal cards atau grid 2 lajur.
4. `Kuiz untuk anda` — kad yang jelas menunjukkan bilangan soalan / status belum cuba.
5. `Apa murid suka` / popular atau kandungan terbaru — gunakan sebagai rail kecil, bukan fokus utama.
6. Empty state berkualiti jika murid belum ada kandungan.

### 6.4 Teroka subjek, Tahun dan bab

Flow:

```text
Teroka → pilih Tahun → pilih Subjek → lihat Bab → lihat kandungan Bab
```

Cadangan UI:

- Tahun selector sebagai compact chip / bottom sheet, bukan dropdown desktop.
- Setiap subjek mempunyai ikon dan accent colour tersendiri yang aksesibel.
- Bab list menunjukkan nombor bab, tajuk dan ringkasan kandungan:
  - jumlah video,
  - bahan,
  - kuiz,
  - status progress murid jika ada.
- Screen Bab memisahkan section `Video`, `Bahan`, dan `Kuiz`.

### 6.5 Video player / lesson detail

Ini ialah salah satu skrin paling penting.

Struktur yang dicadangkan:

```text
Top app bar: back + favourite + more
Player (16:9)
Progress/resume banner jika perlu
Subjek · Tahun · Bab
Tajuk video
Nama guru / penerangan pendek
Tabs atau sections:
  - Bahan sokongan
  - Kuiz berkaitan
  - Video seterusnya
Bottom sticky CTA: Sambung / Seterusnya (jika sesuai)
```

Keadaan penting:

- Video belum mula.
- Video sedang ditonton.
- Ada resume position.
- Video selesai.
- Video tidak dapat dimainkan / internet lemah.
- Tiada bahan atau kuiz.

### 6.6 Kegemaran dan simpanan

Gunakan segmented control atau dua tab di dalam satu screen:

- `Kegemaran`
- `Simpanan`

Setiap item perlu ada thumbnail, tajuk, subjek/bab, status progress dan overflow menu.

### 6.7 Kuiz saya

Cadangkan tiga tab:

- `Untuk dibuat`
- `Sedang berjalan`
- `Selesai`

Setiap kuiz card:

- jenis (interaktif / fail),
- subjek dan bab,
- bilangan soalan atau duration,
- status percubaan,
- CTA sesuai (`Mula`, `Sambung`, `Lihat keputusan`, `Muat turun`).

### 6.8 Ranking

Susun atur:

- Header dengan Tahun aktif + filter Subjek.
- Podium minimal untuk top 3, jangan terlalu seperti game.
- Senarai ranking ringkas.
- Kad “Kedudukan anda” yang kekal kelihatan / pinned di bawah jika perlu.
- State jika murid belum mendapat mata.

### 6.9 Profil murid

Papar:

- Avatar / initials.
- Nama dan nama pengguna.
- Tahun murid.
- Bahasa: BM / EN.
- Tema: terang / gelap (future design boleh sediakan).
- Tukar kata laluan.
- Log keluar.

---

## 7. Modul Guru — senarai skrin dan UX

### 7.1 Teacher app shell / navigation

Cadangan phone: **4 tab + floating action button**

| Tab | Kandungan |
|---|---|
| Dashboard | Ringkasan, aktiviti dan quick actions |
| Kandungan | Video, bahan, kuiz dan bab |
| Analitik | Statistik kuiz, ranking, skor bakat |
| Profil | Akaun, YouTube, tetapan, logout |

FAB pada skrin Dashboard/Kandungan membuka action sheet:

- Tambah video
- Muat naik bahan
- Cipta kuiz
- Tambah bab

Untuk tablet, guna navigation rail + persistent `Tambah kandungan` button di bahagian atas kanan.

### 7.2 Teacher dashboard

Papar dengan tujuan “Apa yang berlaku pada kandungan saya?”

Komponen:

1. Header nama guru dan CTA `Tambah kandungan`.
2. Summary stat grid:
   - Video,
   - Bahan,
   - Kuiz,
   - Tontonan / percubaan terkini.
3. Quick actions.
4. Aktiviti / percubaan kuiz terkini.
5. Kad ringkas Skor Bakat Saya (bukan leaderboard agresif).

### 7.3 Content hub

Satu skrin yang mengandungi tab atau segmented control:

- Video
- Bahan
- Kuiz
- Bab

Sediakan:

- Search.
- Filter Subjek dan Tahun melalui bottom sheet.
- Status chip `Diterbitkan` / `Draf atau disembunyikan`.
- Empty state + CTA pertama.
- Overflow menu setiap item: edit, publish/unpublish, delete.

### 7.4 Tambah / sunting video

Flow mudah, multi-step jika perlu:

```text
1. Pilih sumber: Upload atau YouTube
2. Pilih Subjek → Tahun → Bab
3. Tajuk, penerangan, thumbnail
4. Status: terbitkan atau simpan sebagai draf
5. Semak dan simpan
```

Butiran UX:

- Source selector perlu sangat jelas.
- Upload video perlu menunjukkan progress upload, saiz fail, cancel/retry dan state internet lemah.
- YouTube perlu field URL + maklumat ownership.
- Label ownership:
  - `Muat naik` — kandungan sendiri, dikira untuk skor bakat.
  - `YouTube — Anda` — channel telah disahkan, dikira.
  - `YouTube — Rujukan` — boleh ditonton tetapi tidak dikira.
- Jika YouTube reference, tunjuk explanation yang sopan, bukan error merah.

### 7.5 Bahan pembelajaran

Skrin list dan form upload.

Form memerlukan:

- Subjek → Tahun → Bab.
- Tajuk dan penerangan.
- Pemilih fail dengan jenis yang jelas.
- Status publish.

Card list perlu tunjuk type fail, tajuk, bab, status dan tarikh ringkas.

### 7.6 Kuiz

#### Pilih jenis kuiz

Skrin awal dengan dua large selectable cards:

1. `Kuiz interaktif` — murid jawab dalam aplikasi.
2. `Kuiz fail` — guru muat naik dokumen untuk murid download.

#### Kuiz interaktif

Form metadata:

- Tajuk, penerangan.
- Subjek → Tahun → Bab.
- Duration optional.
- Publish status.

Question builder mesti mobile-friendly:

- Satu question card per soalan.
- Type toggle: `Satu jawapan` / `Pelbagai jawapan`.
- Setiap option mempunyai text field dan switch/tick jawapan betul.
- Button tambah pilihan dan tambah soalan.
- Reorder questions boleh diletakkan sebagai future enhancement jika tiada backend sorting drag-drop.
- Validation jelas: question mesti ada pilihan; radio mesti tepat satu jawapan betul; checkbox sekurang-kurangnya satu jawapan betul.

#### Statistik kuiz

Papar:

- Bilangan percubaan selesai.
- Purata markah dan purata peratus.
- Senarai murid/percubaan.
- Analisis soalan: kadar betul bagi setiap soalan.

Design perlu membantu guru melihat “konsep mana murid belum faham”, bukan hanya angka.

### 7.7 Bab

Guru boleh melihat, tambah, rename, kemas kini atau padam Bab dalam pasangan Subjek + Tahun yang ditawarkan.

UI:

- Pilihan Subjek dan Tahun.
- List bab bernombor.
- Inline add atau modal/bottom sheet `Tambah Bab`.
- Beri warning lembut jika bab tidak aktif atau tidak lagi ditawarkan oleh kurikulum.

### 7.8 Analitik guru dan ranking

Analitik guru boleh mempunyai tab:

- `Kuiz` — statistik dan percubaan.
- `Ranking murid` — filter Tahun/Subjek/Kuiz.
- `Bakat saya` — signal penglibatan kandungan sendiri.

### 7.9 Skor Bakat Saya

Ini bukan penilaian muktamad guru. Ia ialah signal untuk semakan lanjut berdasarkan engagement dalam platform terhadap kandungan sendiri.

Papar:

- Headline score `/100`, atau `Data belum mencukupi`.
- Bilangan murid terlibat.
- Empat component cards / bars:
  1. **Penglibatan** — tontonan unik + kegemaran berpemberat.
  2. **Kualiti** — kadar kegemaran per penonton.
  3. **Hasil Pembelajaran** — beza ketepatan kuiz penonton vs purata bab.
  4. **Keluasan** — jumlah bab yang disumbang.
- Pecahan setiap video: ownership, jangkauan, kegemaran, tamat tonton.
- Disclaimer wajib:

> Skor ini mencerminkan penglibatan dalam platform untuk kandungan guru sendiri. Ia satu petunjuk untuk semakan lanjut, bukan penilaian muktamad kualiti pengajaran dan tidak menggunakan kiraan tontonan awam YouTube.

Jika data tidak mencukupi, jangan tunjuk graph kosong seolah-olah gagal. Guna educational empty state yang jelas.

### 7.10 Sambungkan YouTube

Skrin/profile section untuk YouTube ownership:

- Terangkan bahawa sistem hanya membaca senarai channel untuk pengesahan.
- Tiada token OAuth disimpan secara kekal.
- Tunjuk channel yang disahkan: thumbnail, nama dan tarikh verify.
- CTA `Sambung akaun` / `Sambung lagi`.
- Button `Putuskan` dengan confirmation dialog.
- Terangkan kesan disconnect: video channel tersebut tidak lagi dikira untuk skor bakat.

---

## 8. Aliran utama end-to-end

### 8.1 Murid belajar

```text
Log masuk
  → Utama Murid
  → pilih Subjek / Sambung video
  → Bab
  → tonton video
  → progress disimpan
  → buka bahan atau mula kuiz
  → lihat keputusan
  → lihat ranking / sambung belajar kemudian
```

### 8.2 Guru menerbitkan video

```text
Log masuk guru
  → Dashboard atau Kandungan
  → Tambah video
  → pilih Upload atau YouTube
  → pilih Subjek → Tahun → Bab
  → isi tajuk dan penerangan
  → upload / validate YouTube
  → pilih publish atau draft
  → video muncul kepada murid jika diterbitkan
```

### 8.3 Guru mencipta kuiz

```text
Kandungan → Kuiz → Cipta kuiz
  → pilih interaktif atau fail
  → isi metadata
  → tambah soalan (untuk interaktif)
  → validation jawapan betul
  → publish
  → murid menjawab
  → guru melihat statistik
```

### 8.4 Role routing

```text
Backend mengesahkan login
  → pulangkan user + role
  → student: buka Student navigation
  → teacher: buka Teacher navigation
  → admin: arahkan ke portal web MOE
```

---

## 9. State design yang wajib disediakan

Untuk setiap list/screen relevan, design semua keadaan ini:

| State | Contoh |
|---|---|
| Loading | Skeleton video cards, skeleton dashboard stats |
| Empty | Belum ada video, belum ada kegemaran, belum ada kuiz |
| Error | Internet lemah, video gagal dimuatkan, upload gagal |
| Success | Video diterbitkan, kuiz disimpan, favourite berjaya |
| Offline | Kandungan tersimpan boleh dibuka; kandungan online beri CTA retry |
| Permission | Kamera/media/file permission untuk upload/download jika diperlukan |
| Confirmation | Padam video, padam kuiz, disconnect YouTube, log keluar |

Empty state mesti ada:

- ikon atau ilustrasi ringkas,
- tajuk jelas,
- penerangan satu ayat,
- CTA yang betul apabila pengguna boleh bertindak.

---

## 10. Responsive design: telefon dan tablet

### Telefon

- Single-column content.
- Bottom navigation.
- FAB untuk tindakan tambah kandungan guru.
- Filter, menu dan selectors dalam bottom sheet.
- Video player full-width dengan safe area.

### Tablet

- Navigation rail di kiri.
- Dashboard cards dalam grid 2–4 kolum bergantung lebar.
- Student subject grid lebih luas.
- Teacher content list boleh guna two-pane layout:
  - kiri: list content,
  - kanan: preview/detail atau edit form.
- Quiz builder lebih selesa dalam layout dua kolum pada landscape.

Breakpoints hanyalah panduan; gunakan adaptive layout berdasarkan ruang sebenar, bukan device name semata-mata.

---

## 11. Component system yang perlu direka

Bina design system kecil yang boleh digunakan semula.

### Foundations

- Color tokens.
- Spacing scale: 4, 8, 12, 16, 20, 24, 32.
- Radius tokens.
- Text styles.
- Elevation/shadow tokens.
- Icon sizes.

### Reusable components

- App header / account avatar.
- Primary, secondary, danger dan text button.
- Input field, password input, search input.
- Subject chip dan filter chip.
- Status chip: `Diterbitkan`, `Draf`, `Selesai`, `Latihan`, `YouTube — Anda`, `YouTube — Rujukan`.
- Video card: thumbnail, source, duration, progress, favourite.
- Material file card.
- Quiz card.
- Chapter row.
- Empty state.
- Error/retry state.
- Metric card.
- Progress bar/ring.
- Bottom sheet selector.
- Confirmation dialog.
- Toast/snackbar.
- Section title dengan `Lihat semua`.

---

## 12. Handoff yang diminta daripada Claude

Hasilkan cadangan design Flutter yang lengkap, bukan penerangan umum sahaja.

### Deliverable yang dikehendaki

1. **Visual direction ringkas** — bagaimana mobile design berbeza daripada web tetapi masih LMS MOE.
2. **Design tokens** — warna, typography, spacing, radius, elevation.
3. **Information architecture** — navigation murid dan guru untuk phone/tablet.
4. **Screen-by-screen specification** untuk semua skrin prioriti.
5. **Component inventory** dan state variants.
6. **User flows** untuk login, belajar video, kuiz, tambah video, dan cipta kuiz.
7. **Flutter UI architecture suggestion** — reusable widgets, feature folders, adaptive layout strategy.
8. **High-fidelity Flutter UI code atau mockup** untuk sekurang-kurangnya:
   - Login;
   - Student Home;
   - Subject/Bab detail;
   - Video lesson player;
   - Quiz question + result;
   - Teacher Dashboard;
   - Teacher Content Hub;
   - Add Video;
   - Teacher Quiz Builder;
   - Teacher Talent Score.
9. Tunjukkan loading, empty, error dan success states untuk sekurang-kurangnya Student Home, Content Hub dan Add Video.

### Keutamaan pembinaan UI

```text
P0 — Login + role routing + app shell
P1 — Student Home → Subject → Bab → Video
P2 — Student quiz + result + favourites/simpanan
P3 — Teacher Dashboard + Content Hub + Video upload form
P4 — Materials + quiz builder + quiz statistics
P5 — Ranking + talent score + YouTube connection
```

---

## 13. Prompt ringkas yang boleh terus diberi kepada Claude

Salin teks berikut bersama dokumen ini jika perlu:

```text
Anda ialah senior product designer dan Flutter UI architect. Reka aplikasi mobile LMS MOE untuk sekolah rendah Malaysia berdasarkan design brief yang dilampirkan.

Gunakan Bahasa Melayu sebagai default UI. Aplikasi mempunyai dua role: Murid dan Guru. Selepas login, role menentukan app shell yang berbeza. Kekalkan DNA visual web sedia ada: teal #0F766E, off-white background #F7F8FA, ink #0F172A, kad putih dengan radius lembut. Namun jangan hasilkan salinan kecil web. Jadikan ia mobile-native dengan bottom navigation, bottom sheets, progress states, touch targets besar, dan adaptive tablet layout.

Prioritikan: Login, Student Home, Subject/Bab, Video Player, Quiz, Teacher Dashboard, Content Hub, Add Video, Quiz Builder, dan Teacher Talent Score. Sediakan design system, user flow, responsive behaviour untuk phone/tablet, loading/empty/error/success states, dan Flutter component architecture. Jangan reka admin KPM mobile penuh; admin kekal web-first.
```

---

## 14. Nota teknikal untuk masa depan

Reka bentuk ini tidak perlu mengubah web Laravel. Aplikasi Flutter akan memerlukan mobile API yang diluluskan pada fasa integrasi sebenar.

Flutter akan menerima sekurang-kurangnya data berikut daripada backend selepas login:

```json
{
  "user": {
    "id": 1,
    "name": "Nama Pengguna",
    "username": "nama.pengguna",
    "role": "student",
    "grade": {
      "id": 4,
      "level": 4,
      "name": "Tahun 4"
    }
  }
}
```

Jangan reka UI bergantung pada data yang tidak wujud tanpa fallback yang jelas. Semua design perlu selesa apabila data masih kosong kerana LMS baru mungkin belum banyak video, bahan atau kuiz.
