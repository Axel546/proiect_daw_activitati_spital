# Managementul Activităților Spitalului  
### Proiect Dezvoltare Aplicații Web – PHP & MySQL

## Descriere Generală

Această aplicație web reprezintă un sistem de management al activităților unui spital, dezvoltat în PHP și MySQL, conform cerințelor cursului de **Dezvoltare Aplicații Web**.

Aplicația permite gestionarea activităților spitalului pe departamente, cu autentificare, control al accesului pe bază de roluri și funcționalități administrative. Proiectul este dezvoltat local folosind **XAMPP** și este destinat rulării pe **InfinityFree (shared hosting)**.

---

## Tehnologii Utilizate

- **Backend:** PHP 8.x  
- **Bază de date:** MySQL  
- **Server local:** XAMPP (Apache + MySQL)  
- **Frontend:** HTML, CSS  
- **Bibliotecă externă:** FPDF (export PDF)

---

## Funcționalități Principale

- Autentificare utilizatori (înregistrare, login, logout)
- Control acces bazat pe roluri:
  - **Admin** – acces complet
  - **Staff / Nurse** – gestionare activități proprii
  - **Doctor** – acces doar pentru vizualizare
- Operații CRUD complete pentru activități
- Asocierea activităților cu departamente
- Filtrare activități (departament, status, perioadă)
- Protecție CSRF pe toate formularele
- Pattern PRG (Post–Redirect–Get)
- Analitică website (vizualizări pagini și vizitatori)
- Formular de contact cu protecție anti-bot
- Integrare date externe (feed RSS WHO – alerte de sănătate)
- Generare rapoarte:
  - **PDF** (FPDF)
  - **CSV** (compatibil Excel)

---

## Structura Aplicației
proiect_daw_activitati_spital/
├── public/ # Pagini accesibile public (entry points)
├── src/ # Logică aplicație și funcții helper
├── views/ # Layout reutilizabil
├── config/ # Configurare aplicație
├── database/ # Schema și date de test
├── libs/ # Biblioteci externe (FPDF)
└── README.md


---

## Configurare Locală (XAMPP)

### Cerințe
- XAMPP instalat
- Apache și MySQL pornite

### Pași de Configurare
1. Clonează repository-ul în directorul `htdocs`
2. Creează baza de date importând:
   - `database/schema.sql`
   - `database/seed.sql`
3. Copiază fișierul de configurare:
config/local.php.example → config/local.php
4. Completează datele de conectare la baza de date în `config/local.php`
5. Accesează aplicația în browser:
http://localhost/proiect_daw_activitati_spital/public/

---

## Utilizatori de Test (Seed)

| Rol    | Email                     | Parolă      |
|--------|---------------------------|-------------|
| Admin  | admin@hospital.local      | password123 |
| Staff  | staff.doe@hospital.local  | password123 |
| Doctor | dr.smith@hospital.local   | password123 |

---

## Securitate

- Interogări securizate prin **PDO Prepared Statements**
- Protecție **CSRF** pe toate formularele
- Securizare output HTML pentru prevenirea **XSS**
- Fișierul `config/local.php` nu este comis în Git (conține date sensibile)

---

## Note Tehnice

- Exportul PDF nu utilizează diacritice din cauza limitărilor de codare ale bibliotecii FPDF
- Pe mediul local (localhost), metrica „vizitatori unici” poate rămâne 1 – comportament normal pentru localhost
- Aplicația simulează un sistem intern de management al activităților dintr-un spital

---

## Deployment

Aplicația este compatibilă cu **InfinityFree**, fără a necesita Composer sau extensii suplimentare.  
Este suficientă încărcarea fișierelor și configurarea bazei de date MySQL.

---

## Autor

Proiect realizat pentru cursul **Dezvoltare Aplicații Web** - Zob Alexandru-Mihai
