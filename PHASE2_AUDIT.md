# PHASE 2 - État des Lieux des Modules Critiques
**Date** : 13 juillet 2026  
**Objectif** : Compléter les modules critiques du CDC

---

## 📊 État des Lieux Rapide

### 1. Gestion des Élèves & Inscriptions (60%)

**✅ Existant** :
- StudentController avec index, recherche, filtrage (par classe, matricule, statut)
- Vues : dashboard.blade.php, index.blade.php, show.blade.php
- Recherche avancée (matricule, nom, email)
- Filtrage par classroom_id et status
- Pagination (10 par page)
- Eager loading avec latestRegistration

**❌ Manquant** :
- Upload photo avec redimensionnement 150x150
- Historique scolaire et passage automatique de classe
- Export PDF de la fiche élève individuelle
- CRUD complet (create, update, destroy)
- Fiche détaillée complète

---

### 2. Gestion des Classes (50%)

**✅ Existant** :
- ClassroomController avec index, create, store
- Détermination automatique du cycle (primaire/college/lycee)
- Intégration avec SchoolYear active
- Gestion des niveaux et sections
- ClassroomPolicy pour autorisation

**❌ Manquant** :
- Taux de remplissage (élèves inscrits / capacité)
- Vue détaillée d'une classe (liste élèves, moyennes, emploi du temps)
- Capacité des classes
- Update et destroy
- Salle de classe

---

### 3. Gestion des Professeurs (70%)

**✅ Existant** :
- TeacherController avec index, recherche, filtrage
- Vues complètes : _form, create, edit, index, show, dashboard, pdf
- Ancienneté calculée automatiquement (date_recrutement)
- Recherche par nom, email, matricule, statut, matière, ancienneté
- Gestion des matières enseignées (relation many-to-many avec Classroom)
- Export PDF existant (pdf.blade.php)
- Form Requests : StoreTeacherRequest, UpdateTeacherRequest

**❌ Manquant** :
- Alerte si dépassement du volume horaire
- Volume horaire hebdomadaire dans la vue
- CRUD complet (destroy)

---

### 4. Notes & Bulletins (30%) - **PRIORITÉ ABSOLUE**

**✅ Existant** :
- Modèle Note avec relations (student, classroom, matiere)
- Scopes : forPeriod, forType, above
- Accessor : formatted_value
- GradeController pour saisie des notes par professeur
- Vue : teachers/grades/index.blade.php
- Factory NoteFactory

**❌ Manquant** :
- Structure complète 3 trimestres
- Coefficients par matière
- Plusieurs notes par matière
- Calculs automatiques :
  - Moyenne par matière
  - Moyenne générale du trimestre
  - Classement dans la classe
  - Mention (Excellent, Bien, Assez Bien, Passable, Insuffisant)
- **Génération de bulletins PDF** (DOMPDF)
- Logo de l'école, en-tête, tampon
- Tableau des notes + appréciations
- Moyennes et classement

---

### 5. Scolarité & Paiements (40%)

**✅ Existant** :
- PaymentController avec store
- Modèle Payment avec scopes (complete, partial, forMonth, forYear)
- Génération automatique du numéro de reçu
- Validation des paiements partiels (gate)
- Form Request : StorePaymentRequest
- Relation avec Registration

**❌ Manquant** :
- Génération de reçu PDF imprimable
- Tableau de bord de recouvrement (taux de paiement par classe/mois)
- Historique complet par élève
- Alertes pour impayés (visuelles)
- CRUD complet (index, update, destroy)

---

## 🎯 Plan d'Action Prioritaire

### Ordre recommandé par l'utilisateur :
1. ~~Élèves + Classes + Professeurs~~ (reporté après Notes & Bulletins)
2. ~~Paiements~~ (reporté après Notes & Bulletins)
3. **Notes & Bulletins** (PRIORITÉ ABSOLUE)

### Tâches Notes & Bulletins :

1. **Structure des données**
   - Ajouter coefficient dans la table notes ou matieres
   - Créer table bulletins ou utiliser calcul à la volée
   - Définir les 3 périodes (trimestre_1, trimestre_2, trimestre_3)

2. **Calculs automatiques**
   - Moyenne par matière (pondérée par coefficient)
   - Moyenne générale du trimestre
   - Classement dans la classe
   - Mention selon moyenne

3. **Saisie des notes**
   - Améliorer l'interface existante
   - Ajouter gestion des coefficients
   - Permettre plusieurs notes par matière

4. **Bulletins PDF**
   - Installer/configurer DOMPDF
   - Créer vue bulletin.blade.php
   - Générer PDF individuel et en masse
   - Logo, en-tête, tampon

---

## 📋 Statut Global

| Module | État | Priorité |
|--------|------|----------|
| Élèves | 60% | Moyenne |
| Classes | 50% | Moyenne |
| Professeurs | 70% | Moyenne |
| **Notes & Bulletins** | **30%** | **HAUTE** |
| Paiements | 40% | Moyenne |

---

**Conclusion** : Le module Notes & Bulletins est le moins avancé et le plus critique selon le CDC. Je vais commencer par ce module.
