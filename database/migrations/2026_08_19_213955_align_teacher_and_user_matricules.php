<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Le compte utilisateur et la fiche enseignant d'un même professeur doivent
     * partager le même matricule — c'est ce qui les identifie comme la même personne
     * dans les écrans "Utilisateurs" et "Affectations pédagogiques"/"Professeurs".
     * Ils étaient générés indépendamment (User::generateMatricule('professeur') d'un
     * côté, Teacher::generateMatricule() de l'autre — corrigé dans le même changement
     * pour qu'ils partagent désormais un seul matricule à la création), et un ancien
     * enregistrement seedé les a fait dériver d'un cran : depuis, chaque nouveau
     * professeur avait un matricule différent selon l'écran consulté (ex. PROF-26-0008
     * désignait un professeur côté "Affectations" et une tout autre personne côté
     * "Utilisateurs").
     *
     * On réaligne users.matricule sur teachers.matricule (la valeur affichée aux
     * classes/affectations) en deux passes — une passe intermédiaire par valeur
     * temporaire unique évite toute violation de la contrainte unique users.matricule
     * pendant la rotation en chaîne des valeurs.
     */
    public function up(): void
    {
        $pairs = DB::table('teachers')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->whereColumn('users.matricule', '!=', 'teachers.matricule')
            ->select('teachers.user_id', 'teachers.matricule')
            ->get();

        foreach ($pairs as $pair) {
            DB::table('users')->where('id', $pair->user_id)->update([
                'matricule' => 'TMP-REALIGN-'.$pair->user_id,
            ]);
        }

        foreach ($pairs as $pair) {
            DB::table('users')->where('id', $pair->user_id)->update([
                'matricule' => $pair->matricule,
            ]);
        }
    }

    public function down(): void {}
};
