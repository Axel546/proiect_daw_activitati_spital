# Managementul Activităților Spitalului  
### Proiect Dezvoltare Aplicații Web – PHP & MySQL

Aplicație web dezvoltată în PHP și MySQL pentru gestionarea activităților unui spital, organizate pe departamente, cu autentificare, control al accesului pe roluri, rapoarte și funcționalități administrative.

Proiect realizat ca aplicație universitară pentru cursul de **Dezvoltare Aplicații Web**.

---

## Contextul Proiectului

Aplicația simulează un sistem intern de management al activităților dintr-un spital:
- activități medicale și administrative
- utilizatori cu roluri diferite
- departamente medicale
- rapoarte și statistici
- integrare de date externe

Aplicația este dezvoltată local folosind **XAMPP** și este destinată rulării pe un hosting de tip **shared hosting (InfinityFree)**.

---

## Tehnologii Utilizate

- **Backend:** PHP 8+
- **Bază de date:** MySQL (PDO, prepared statements)
- **Server local:** Apache (XAMPP)
- **Frontend:** HTML, CSS, Bootstrap 5
- **Securitate:** CSRF protection, hashing parole, validare server-side
- **Rapoarte:** PDF (FPDF), CSV
- **Hosting:** InfinityFree

---

## Structura Proiectului
proiect_daw_activitati_spital/
├── index.php
├── login.php
├── register.php
├── logout.php
├── contact.php
├── external.php
├── unauthorized.php
├── activities/
│ ├── index.php
│ ├── create.php
│ ├── edit.php
│ ├── show.php
│ └── delete.php
├── reports/
│ ├── activities.php
│ ├── activities_pdf.php
│ └── activities_csv.php
├── admin/
│ ├── analytics.php
│ └── messages.php
├── app/
│ ├── src/
│ │ ├── db.php
│ │ ├── helpers.php
│ │ ├── auth.php
│ │ ├── csrf.php
│ │ ├── analytics.php
│ │ ├── external_feed.php
│ │ └── reports_helper.php
│ ├── views/
│ │ └── layout.php
│ ├── config/
│ │ ├── local.php.example
│ │ └── local.php (necomitat)
│ ├── libs/
│ │ ├── fpdf.php
│ │ └── font/
│ └── database/
│ ├── schema.sql
│ └── seed.sql
├── .gitignore
└── README.md

---

## Configurare Locală (XAMPP)

### Cerințe
- XAMPP instalat
- Apache și MySQL pornite

### Pași

1. Copiază proiectul în:
C:\xampp\htdocs\proiect_daw_activitati_spital

2. Creează baza de date:
- Deschide `http://localhost/phpmyadmin`
- Importă `app/database/schema.sql`
- Importă `app/database/seed.sql` (date de test)

3. Configurează conexiunea la DB:
- Copiază `app/config/local.php.example` → `app/config/local.php`
- Editează credențialele MySQL

4. Accesează aplicația:
http://localhost/proiect_daw_activitati_spital/

---

## Hosting pe InfinityFree

- Fișierele publice sunt plasate direct în `htdocs/`
- Codul intern este izolat în folderul `app/`
- URL final **fără `/public`**

Exemplu:
https://management-spital-daw.infinityfreeapp.com/

---

## Funcționalități Implementate

### Autentificare și Utilizatori
- Înregistrare utilizatori
- Autentificare / Deconectare
- Parole criptate (`password_hash`)
- Rol implicit: **staff**

### Roluri și Permisiuni
- **Admin** – acces complet
- **Staff / Nurse** – CRUD doar pentru activitățile proprii
- **Doctor** – acces doar la citire

### Management Activități
- Creare, editare, ștergere activități
- Asociere cu departamente
- Statusuri: Planificat, În progres, Completat, Anulat
- Filtrare după departament, status și dată

### Rapoarte
- Export PDF (FPDF)
- Export CSV
- Previzualizare activități
- Funcționează fără Composer

### Analitică Website
- Tracking vizualizări pagini
- Vizitatori unici (hash IP)
- Dashboard admin
- Notă: pe localhost vizitatorii unici pot fi 1 (comportament normal)

### Formular de Contact
- CSRF protection
- Honeypot anti-bot
- Rate limiting
- Salvare mesaje în DB
- Panou admin pentru mesaje

### Integrare Date Externe
- Știri și alerte de sănătate (RSS WHO)
- Cache în baza de date
- Refresh manual pentru admin

### Interfață
- Bootstrap 5
- Responsive (desktop & mobile)
- Tabele cu scroll pe mobil
- Layout unitar

---

## Conturi Demo (seed.sql)

- **Admin:**  
  `admin@hospital.local` / `password123`

- **Staff:**  
  `staff.doe@hospital.local` / `password123`

- **Doctor:**  
  `dr.smith@hospital.local` / `password123`

---

## Securitate

- PDO + prepared statements
- Protecție CSRF
- Escapare output HTML
- Separare cod public / privat
- Fără date sensibile în GitHub

---

## Observații Finale

- Proiectul este complet funcțional
- Structură profesională, compatibilă cu hosting shared
- Ușor de extins (panou admin utilizatori, notificări etc.)

---

**Autor:**  
Proiect realizat ca temă pentru cursul de Dezvoltare Aplicații Web de Zob Alexandru Mihai

