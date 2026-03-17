# 3. un 4. Uzdevuma Salīdzinājums: Transakcijas Implementācija

## Pārskats

Abi uzdevumi risina to pašu problēmu - prakses pieteikuma izveidi ar datu validāciju un transakciju, bet izmanto dažādas pieejas:

- **3. uzdevums**: Transakcija aplikācijas līmenī (PHP/Laravel)
- **4. uzdevums**: Transakcija datubāzes līmenī (MySQL Stored Procedure)

---

## 3. Uzdevums: Aplikācijas Līmeņa Transakcija

### Implementācija
- Vieta: `app/Services/ApplicationService.php`
- Metode: `createApplication()`
- Izmanto: `DB::transaction()` no Laravel

### Kods
```php
public function createApplication(...): Application {
    return DB::transaction(function () use (...) {
        $student = $this->validateUserExists($studentId);
        $internship = $this->validateInternshipIsValid($internshipId);
        $this->validateUserCanApply($student, $internship);
        $this->validateMotivationLetter($motivationLetter);
        
        $application = Application::create([...]);
        return $application->fresh([...]);
    });
}
```

### Priekšrocības

| Priekšrocība | Apraksts |
|-------------|----------|
| **Lasāmība** | PHP kods ir vieglāk lasāms izstrādātājiem, kas nepārzina SQL |
| **Testējamība** | Vienkāršāk testēt ar PHPUnit, mock objektu izmantošana |
| **Laravel ekosistēma** | Izmanto Eloquent ORM, Resource klases, validāciju |
| **Kļūdu apstrāde** | Laravel exception handling, logging integrācija |
| **Mainīgais līmenis** | Vieglāk mainīt biznesa loģiku bez DB migrācijām |
| **Dependency Injection** | Var injicēt servisus, vieglāk uzturēt |
| **Type safety** | PHP type hints, IDE atbalsts |

### Trūkumi

| Trūkums | Apraksts |
|---------|----------|
| **Network overhead** | Vairāki DB vaicājumi (vairāki round-trips) |
| **Performance** | Lēnāks pie lielas slodzes |
| **DB agnostic** | Neizmanto DB specifiskas optimizācijas |
| **Code duplication** | Validācijas loģika dublējas ar DB constraints |

---

## 4. Uzdevums: Datubāzes Procedūra

### Implementācija
- Vieta: `database/migrations/2026_03_17_115843_create_create_application_procedure.php`
- Procedūra: `create_application()`
- Izmanto: MySQL Stored Procedure ar `START TRANSACTION` / `COMMIT` / `ROLLBACK`

### Kods
```sql
CREATE PROCEDURE create_application(
    IN p_internship_id INT,
    IN p_student_id INT,
    IN p_company_id INT,
    IN p_motivation_letter TEXT,
    OUT p_application_id INT,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_application_id = NULL;
        SET p_error_message = 'Datubāzes kļūda...';
    END;
    
    START TRANSACTION;
    
    -- Validācijas
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_student_id) THEN
        ROLLBACK;
        -- ...
    END IF;
    
    COMMIT;
END
```

### Priekšrocības

| Priekšrocība | Apraksts |
|-------------|----------|
| **Performance** | Viens vaicājums, mazāks network overhead |
| **Atomicitāte** | Pilnīga transakcijas kontrole DB līmenī |
| **DB constraints** | Tieša piekļuve tabulām, ātrākas validācijas |
| **Centralizācija** | Loģika vienā vietā neatkarīgi no aplikācijas |
| **Multiple apps** | Vairākas aplikācijas var izmantot to pašu procedūru |
| **Security** | Var ierobežot tiešu piekļuvi tabulām |

### Trūkumi

| Trūkums | Apraksts |
|---------|----------|
| **Vendor lock-in** | MySQL specifisks risinājums |
| **Testējamība** | Grūtāk testēt, nepieciešamas DB fixtures |
| **Debugging** | Grūtāk debuggot nekā PHP kodu |
| **Version control** | Migrācijas jāuztur sinhronizētas |
| **Maintenance** | SQL procedūras grūtāk refactorēt |
| **No ORM** | Nevar izmantot Eloquent attiecības |
| **Limited logic** | Sarežģīta loģika grūti implementējama |

---

## Salīdzinošā Tabula

| Kritērijs | 3.uzdevums (PHP) | 4.uzdevums (SP) | Uzvarētājs |
|-----------|------------------|-----------------|------------|
| **Veiktspēja** | Vidēja | Augsta | 4.uzdevums |
| **Lasāmība** | Augsta | Vidēja | 3.uzdevums |
| **Testējamība** | Augsta | Zema | 3.uzdevums |
| **Uzturēšana** | Viegla | Sarežģīta | 3.uzdevums |
| **Portabilitāte** | Augsta | Zema | 3.uzdevums |
| **Drošība** | Vidēja | Augsta | 4.uzdevums |
| **Scalability** | Vidēja | Augsta | 4.uzdevums |
| **Developer UX** | Labs | Vidējs | 3.uzdevums |

---

## Ieteikumi

### Kad izmantot 3.uzdevuma pieeju (PHP):
- ✅ Mazi un vidēji projekti
- ✅ Agile/iteratīva izstrāde
- ✅ Biežas izmaiņas biznesa loģikā
- ✅ Komanda zina PHP, nevis SQL
- ✅ Nepieciešama ātra testēšana
- ✅ Plānots migrēt uz citu DB

### Kad izmantot 4.uzdevuma pieeju (Stored Procedure):
- ✅ Liela datu slodze
- ✅ Kritiska veiktspēja
- ✅ Vairākas aplikācijas lieto vienu DB
- ✅ Stabilā biznesa loģika
- ✅ Drošības prasības prasa DB līmeņa kontroli
- ✅ Komandai ir SQL eksperti

---

## Secinājums

**3.uzdevums (PHP transakcija)** ir piemērotākais vairumam Laravel projektu, jo:
1. Labāka izstrādātāja pieredze
2. Vienkāršāka uzturēšana
3. Labāka testējamība
4. Portabilitāte

**4.uzdevums (Stored Procedure)** ir specializēts risinājums, kas attaisnojams:
1. Ļoti augstas veiktspēzes prasībām
2. Multi-application arhitektūrām
3. Kad DB ir "single source of truth"

**Hybrid pieeja** var būt optimāla: izmantot PHP transakcijas kā galveno risinājumu, bet kritiskām operācijām izmantot DB procedūras.

---

## Version Information

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0 | 2026-03-17 | Internship Manager | Initial comparison document |

### Files Changed

**3.uzdevums:**
- `app/Services/ApplicationService.php` - Transakcijas serviss

**4.uzdevums:**
- `database/migrations/2026_03_17_115843_create_create_application_procedure.php` - DB procedūras migrācija
- `app/Http/Controllers/ApplicationController.php` - `storeWithProcedure()` metode
- `routes/api.php` - `POST api/applications-procedure` maršruts
