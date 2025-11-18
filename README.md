# 📝 Sistem Siap Ingat
Sistem Siap Ingat adalah platform manajemen kelas dan pengingat (reminder) yang digunakan oleh Admin, Penanggung Jawab (PJ) Kelas, dan Murid untuk mengelola kegiatan, informasi kelas, serta pengingat secara terpusat.

---

## 🚀 Fitur Utama

### 👑 Admin
Admin memiliki akses penuh untuk mengelola seluruh data dalam sistem:

- Mengelola **Role**
- Mengelola **User**
- Mengelola **Murid**
- Mengelola **Kelas**
- Mengelola **Murid dalam Kelas**
- Mengelola **Kategori Reminder**
- Mengelola **Reminder** (Global & Per Kelas)
- Mengelola **Profil**
- **Ganti Password**

---

### 🧑‍🏫 PJ Kelas
PJ Kelas memiliki akses terbatas pada kelas yang mereka tangani:

- Melihat & mengelola **informasi kelas**
- **Mengganti token kelas**
- Mengelola **Murid dalam Kelas**
- Membuat **Reminder khusus kelas**
- Melihat **Reminder Global** & **Reminder Kelas**
- Mengelola **Profil**
- **Ganti Password**

---

### 🎓 Murid
Murid hanya dapat melihat data yang relevan dengan kelasnya:

- Melihat **Informasi Kelas**
- Melihat **Reminder Global & Reminder Kelas**
- Mengelola **Profil**
- **Ganti Password**

---

## 🔄 Alur Sistem

### 1️⃣ Registrasi
- Jika user mendaftar sebagai **Murid**, akun langsung dibuat.
- Jika user seharusnya menjadi **PJ Kelas**, maka **Admin** yang akan menambahkannya dan menetapkan rolenya.

---

### 2️⃣ Pengelolaan Kelas oleh Admin
Admin dapat:
1. Membuat **Kelas baru**
2. Menentukan **PJ Kelas**
3. Menambahkan **Kategori Reminder**
4. Membuat **Reminder Global** atau **Reminder khusus kelas**

---

### 3️⃣ Aktivitas PJ Kelas
PJ Kelas dapat:
- Melihat detail kelas
- **Mengganti token kelas** dan membagikannya ke murid
- Mengelola murid dalam kelas
- Membuat **Reminder untuk kelas tersebut**

---

## 📌 Ringkasan Role
| Role       | Akses                                                                 |
|------------|-----------------------------------------------------------------------|
| **Admin**  | Full control: semua data, kelas, user, reminder, kategori, setting    |
| **PJ Kelas** | Kelola kelasnya sendiri + reminder kelas                             |
| **Murid**  | Lihat informasi kelas & reminder                                       |

---

## 🛠 Teknologi (Opsional)
_Tambahkan bagian ini sesuai stack yang digunakan:_
- Laravel / Node.js / Express / React / Next.js / Vue
- MySQL / PostgreSQL
- TailwindCSS / Bootstrap
- JWT / Sanctum Auth

---

## 📁 Struktur Folder (Opsional)
_Tambahkan setelah project dibuat._

---

## 📄 Lisensi
Sistem ini digunakan untuk kebutuhan internal dan tidak untuk distribusi publik (ubah sesuai kebutuhan).

---

## ✍ Kontributor
- **L9kyuu / Next.Buildapp** – Developer Sistem Siap Ingat

---