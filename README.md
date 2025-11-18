# **📘 Siap Ingat – Dokumentasi Sistem**

Sistem **Siap Ingat** adalah platform pengelolaan kelas dan pengingat (reminder) yang digunakan oleh Admin, PJ Kelas, dan Murid untuk memastikan kegiatan kelas berjalan teratur dan terpantau.

Dokumentasi ini menjelaskan **role**, **hak akses**, dan **alur aktivitas** dalam sistem.

---

## **👤 1. Role & Hak Akses**

### **🛠️ Admin**

Admin memiliki akses penuh untuk mengelola seluruh data dan konfigurasi sistem.

Fitur yang dapat dikelola:

* Role (Admin, PJ Kelas, Murid)
* User
* Murid
* Kelas
* Murid dalam Kelas
* Kategori Reminder
* Reminder:

  * Reminder Global (untuk semua kelas)
  * Reminder Khusus Per Kelas
* Profil
* Setting (Ganti Password)

---

### **👨‍🏫 PJ Kelas**

Penanggung Jawab Kelas hanya mengelola kelas yang ditugaskan kepadanya.

Fitur PJ:

* Melihat dan mengelola informasi kelas
* Mengganti token kelas
* Mengelola murid dalam kelas
* Membuat reminder khusus untuk kelasnya
* Melihat reminder global & reminder kelas

---

### **🎓 Murid**

Murid memiliki akses terbatas.

Fitur murid:

* Melihat informasi kelas yang diikuti
* Melihat seluruh reminder yang berlaku untuk kelas

---

## **🔄 2. Alur Aktivitas Sistem**

### **1️⃣ Registrasi Murid**

* Murid melakukan registrasi melalui sistem.
* Jika murid adalah **PJ Kelas**, maka admin akan menambahkan murid tersebut secara manual dan memberi role PJ.
* Jika bukan PJ, pengguna otomatis menjadi **Murid biasa**.

---

### **2️⃣ Admin Menambahkan Kelas**

Saat membuat kelas baru, admin akan:

1. Memilih PJ Kelas
2. Menambahkan kategori reminder yang diperlukan
3. Membuat reminder:

   * Global (berlaku untuk semua kelas)
   * Khusus untuk kelas tertentu

---

### **3️⃣ Pengelolaan Kelas oleh PJ Kelas**

Setelah kelas aktif:

1. PJ melihat informasi kelas
2. Mengganti token kelas jika diperlukan
   (Token digunakan murid untuk bergabung ke kelas)
3. Membagikan token kelas kepada murid
4. Membuat reminder khusus untuk kelasnya

---

## **📘 Ringkasan Sistem**

* **Admin**: Pusat kontrol seluruh sistem
* **PJ Kelas**: Pengelola kelas masing-masing
* **Murid**: Penerima informasi dan reminder

Alur singkat sistem:
**Registrasi → Pembuatan Kelas → Pengaturan Reminder → Murid menerima Informasi**