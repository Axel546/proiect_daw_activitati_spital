# Managementul Activitatilor Spitalului - Proiect PHP + MySQL

O aplicație web PHP + MySQL pentru gestionarea activităților spitalului pe departamente, care rulează pe XAMPP.

## Contextul Proiectului

Aceasta este o aplicație web universitară pentru managementul activităților spitalului, dezvoltată local folosind XAMPP și destinată pentru deployment pe InfinityFree.

## Configurare Locală

### Cerințe Preliminare
- XAMPP instalat și pornit
- Serviciile Apache și MySQL pornite în XAMPP Control Panel

### Pași de Configurare

1. **Pornește Serviciile XAMPP**
   - Deschide XAMPP Control Panel
   - Pornește Apache
   - Pornește MySQL

2. **Creează Baza de Date**
   - Opțiunea A: Folosind phpMyAdmin
     - Deschide http://localhost/phpmyadmin
     - Click pe tab-ul "Import"
     - Selectează `database/schema.sql`
     - Click "Go"
   - Opțiunea B: Folosind linia de comandă MySQL
     ```bash
     mysql -u root < database/schema.sql
     ```

3. **Importă Date de Test (Recomandat)**
   - În phpMyAdmin, importă `database/seed.sql`
   - Sau via linia de comandă:
     ```bash
     mysql -u root < database/seed.sql
     ```
   - Aceasta va crea roluri, departamente, utilizatori și activități de exemplu

4. **Configurează Conexiunea la Baza de Date**
   - Copiază `config/local.php.example` în `config/local.php`
   - Editează `config/local.php` cu credențialele tale pentru baza de date:
     ```php
     return [
         'db' => [
             'host' => 'localhost',
             'dbname' => 'proiect_test',
             'username' => 'root',
             'password' => ''  // Parola ta MySQL dacă este setată
         ],
         'analytics' => [
             'ip_salt' => 'schimba_acest_string_random_pentru_securitate'
         ],
         'contact' => [
             'admin_email' => 'admin@hospital.local'  // Adresa de email pentru a primi mesajele din formularul de contact
         ]
     ];
     ```
   - **Important**: Schimbă `ip_salt` cu un string random lung pentru securitate

5. **Accesează Aplicația**
   - Deschide browserul și navighează la:
     ```
     http://localhost/proiect_daw_activitati_spital/public/
     ```

## Structura Proiectului

```
proiect_daw_activitati_spital/
├── public/
│   ├── index.php              # Punctul de intrare principal (dashboard)
│   ├── login.php              # Pagină de autentificare
│   ├── register.php           # Pagină de înregistrare
│   ├── logout.php             # Deconectare
│   ├── contact.php             # Formular de contact
│   ├── external.php            # Integrare date externe (știri sănătate)
│   ├── activities/             # Management activități (CRUD)
│   ├── reports/                # Rapoarte și exporturi
│   └── admin/                  # Panou administrator
├── src/
│   ├── db.php                  # Funcție de conexiune la baza de date
│   ├── helpers.php             # Funcții helper reutilizabile pentru baza de date
│   ├── auth.php                # Funcții helper pentru autentificare
│   ├── csrf.php                # Protecție CSRF
│   ├── analytics.php           # Tracking vizitatori și pagini
│   ├── external_feed.php       # Integrare feed extern
│   └── reports_helper.php      # Funcții helper pentru rapoarte
├── views/
│   └── layout.php              # Layout reutilizabil cu navigare
├── config/
│   ├── local.php.example       # Template de configurare (comis în git)
│   └── local.php               # Configurare locală (nu este comisă)
├── database/
│   ├── schema.sql              # Crearea bazei de date și tabelelor
│   └── seed.sql                # Date de test (roluri, departamente, utilizatori, activități)
├── libs/
│   ├── fpdf.php                # Biblioteca FPDF pentru generare PDF
│   └── font/                   # Fișiere de definiție fonturi (din pachetul FPDF)
├── .gitignore
└── README.md
```

## Schema Bazei de Date

- **Nume Baza de Date:** `proiect_test`

### Tabele

- **roles** - Roluri utilizatori (admin, doctor, nurse, staff)
- **departments** - Departamente spital (Cardiologie, Urgențe, Chirurgie, etc.)
- **users** - Utilizatori sistem cu roluri și asignări la departamente
- **activities** - Activități/evenimente spital cu urmărire status
- **analytics_page_views** - Analitică website (vizualizări pagini și tracking vizitatori)
- **contact_messages** - Trimiteri din formularul de contact
- **external_feed_cache** - Date cache pentru feed-ul extern de știri/alarme sănătate

### Relații Cheie

- Utilizatorii aparțin rolurilor și opțional departamentelor
- Activitățile aparțin departamentelor și sunt create de utilizatori
- Activitățile au status: planned (planificat), in_progress (în progres), completed (completat), cancelled (anulat)

## Funcționalități Actuale

- ✅ Sistem de autentificare (înregistrare/login/logout) cu management sesiuni
- ✅ Control acces bazat pe roluri (admin, doctor, nurse, staff)
- ✅ Operații CRUD complete pentru activități (creare, citire, actualizare, ștergere)
- ✅ Listare toate activitățile spitalului cu informații despre departament și creator
- ✅ Filtrare activități (departament, status, interval de date)
- ✅ Pattern PRG (Post-Redirect-Get) pentru a preveni retrimiterea formularelor
- ✅ Protecție CSRF pe toate formularele
- ✅ PDO cu prepared statements pentru securitate
- ✅ Funcții helper reutilizabile pentru baza de date
- ✅ Analitică website (tracking vizualizări pagini și vizitatori unici)
- ✅ Formular de contact cu trimitere email și protecție anti-bot
- ✅ Integrare date externe (știri/alarme sănătate din feed RSS WHO)
- ✅ Rapoarte și exporturi (formate PDF și CSV)
- ✅ Interfață modernă și curată

## Organizarea Codului

- **Helper-uri Reutilizabile**: `src/helpers.php` oferă funcții generice pentru baza de date (dbSelectAll, dbSelectOne, dbInsert, dbExecute)
- **Securitate**: Toate interogările folosesc PDO prepared statements
- **Structură**: Codul este organizat pentru extindere ușoară cu autentificare, permisiuni bazate pe roluri și funcționalități suplimentare

## Biblioteci Externe

Acest proiect folosește FPDF pentru generarea rapoartelor PDF:

### FPDF (Generare PDF)
- **Scop**: Generare rapoarte PDF
- **Locație**: `libs/fpdf.php` și directorul `libs/font/`
- **Instalare**: 
  1. Descarcă FPDF de la: http://www.fpdf.org/en/download.php
  2. Extrage fișierul ZIP
  3. Copiază `fpdf.php` în `libs/fpdf.php`
  4. **IMPORTANT**: Copiază întregul director `font/` din pachetul FPDF în `libs/font/`
     - Acesta conține fișierele de definiție fonturi necesare pentru fonturile de bază (Helvetica, Times, Courier)
     - Fișiere precum `helvetica.php`, `helveticab.php`, `times.php`, etc.
  5. Fișierul `fpdf.php` are aproximativ 48-150KB în funcție de versiune
  6. Directorul `font/` conține fișiere PHP mici (câteva KB fiecare)
- **Notă**: FPDF 1.86 necesită fișiere de definiție fonturi chiar și pentru fonturile încorporate. Acestea sunt incluse în pachetul de descărcare FPDF.
- **Fără Composer Necesar**: FPDF este o bibliotecă standalone, nu este necesar un manager de pachete

### Export CSV
- **Scop**: Alternativă ușoară la exportul Excel
- **Locație**: `public/reports/activities_csv.php`
- **Fără Bibliotecă Necesară**: Folosește funcția PHP built-in `fputcsv()`
- **Compatibil**: Fișierele CSV pot fi deschise în Excel, Google Sheets sau orice aplicație de tip spreadsheet

## Note

- Acesta este un proiect de bază - autentificarea și funcționalitățile avansate vor fi adăugate incremental
- Fișierul de configurare `config/local.php` este gitignored pentru securitate
- Folosește întotdeauna prepared statements pentru a preveni SQL injection
- Funcțiile helper din `src/helpers.php` ar trebui folosite pentru toate operațiile cu baza de date pentru a menține consistența

### Analitică Website

- Sistemul de analitică urmărește automat vizualizările paginilor și vizitatorii unici
- **Important**: Pe localhost, metrica "vizitatori unici" poate rămâne la 1 deoarece toate cererile vin de la aceeași adresă IP (127.0.0.1 sau ::1). Acesta este comportamentul așteptat pentru dezvoltarea locală
- Metrica vizitatori unici va funcționa corect pe deployment-ul InfinityFree găzduit unde vizitatorii au adrese IP diferite
- Vizualizările paginilor sunt urmărite corect atât pe localhost cât și pe mediile de producție

### Integrare Date Externe (Știri/Alarme Sănătate)

- Sistemul preia știri și alarme de sănătate din feed-ul RSS WHO (Organizația Mondială a Sănătății)
- **Cum funcționează:**
  1. Preluare server-side: Sistemul preia feed-ul RSS de la URL-ul oficial al feed-ului WHO
  2. Parsare: XML-ul RSS este analizat și normalizat într-un format structurat (titlu, dată, rezumat, link)
  3. Cache: Datele feed-ului sunt stocate în cache în baza de date timp de 45 de minute pentru a reduce încărcarea sursei externe
  4. Fallback: Dacă sursa externă nu este disponibilă, sistemul afișează ultimele date din cache cu un avertisment
  5. Afișare: Ultimele 10 elemente sunt afișate pe pagina Alarme Sănătate cu interfață curată
- **Management cache:**
  - Durata cache: 45 de minute (configurabilă)
  - Utilizatorii admin pot forța reîmprospătarea cache-ului via butonul "Refresh Cache"
  - Cache-ul este stocat în tabelul `external_feed_cache`
- **Acces:** Disponibil pentru toți utilizatorii autentificați via link-ul de navigare "Health Alerts"
- **Securitate:** Toate output-urile sunt escăpate, link-urile externe se deschid în tab-uri noi cu `rel="noopener noreferrer"`
