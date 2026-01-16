# Configurare Biblioteci Externe

Acest director conține biblioteci externe necesare pentru generarea rapoartelor.

## Configurare FPDF

1. Descarcă FPDF de la: http://www.fpdf.org/en/download.php
2. Extrage fișierul ZIP
3. Copiază `fpdf.php` în acest director (`libs/fpdf.php`)
   - Fișierul ar trebui să aibă aproximativ 48-150KB în funcție de versiune
4. **IMPORTANT**: Copiază directorul `font/` din pachetul FPDF
   - În folderul FPDF extras, vei găsi un director `font/`
   - Copiază întregul director `font/` în `libs/font/` în proiectul tău
   - Acest director conține fișiere de definiție fonturi necesare pentru fonturile de bază (Helvetica, Times, Courier)
   - Fișierele font sunt fișiere PHP mici (de ex., `helvetica.php`, `helveticab.php`, etc.)

**Structură Director După Configurare:**
```
libs/
├── fpdf.php
└── font/
    ├── helvetica.php
    ├── helveticab.php
    ├── helveticai.php
    ├── helveticabi.php
    ├── times.php
    ├── timesb.php
    ├── timesi.php
    ├── timesbi.php
    ├── courier.php
    ├── courierb.php
    ├── courieri.php
    └── courierbi.php
```

**Notă**: FPDF 1.86 necesită fișiere de definiție font chiar și pentru fonturile de bază încorporate. Aceste fișiere sunt incluse în pachetul de descărcare FPDF.

## Verificare

După ce plasezi `fpdf.php` în acest director, poți verifica că funcționează prin:
1. Navigare la pagina Rapoarte
2. Aplicare filtre
3. Click pe "Exportă ca PDF"
4. Dacă FPDF este instalat corect, un PDF va fi descărcat

Dacă vezi un mesaj de eroare, asigură-te că:
- Fișierul se numește exact `fpdf.php` (lowercase)
- Fișierul este în directorul `libs/`
- Fișierul conține definiția clasei FPDF
