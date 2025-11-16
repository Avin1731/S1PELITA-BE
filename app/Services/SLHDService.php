<?php

namespace App\services;

class SLHDService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
     private array $babWeights = [
        'Bab_1' => 0.10,
        'Bab_2' => 0.50, // dibagi lagi ke matra
        'Bab_3' => 0.20,
        'Bab_4' => 0.10,
        'Bab_5' => 0.10,
    ];

    // Bobot matra pada BAB 2
    private array $bab2Weights = [
        'Keanekaragaman_Hayati'         => 0.10,
        'Kualitas_Air'                  => 0.10,
        'Laut_Pesisir_dan_Pantai'       => 0.10,
        'Kualitas_Udara'               => 0.10,
        'Lahan_dan_Hutan'              => 0.10,
        'Pengelolaan_Sampah_dan_Limbah'=> 0.25,
        'Perubahan_Iklim'              => 0.15,
        'Risiko_Bencana'               => 0.10,
    ];

    /**
     * Hitung skor total SLHD satu row
     */
    public function calculate(array $row): float
    {
        // Hitung BAB 2
        $bab2Score = 0;
        foreach ($this->bab2Weights as $col => $weight) {
            $value = floatval($row[$col] ?? 0);
            $bab2Score += $value * $weight;  // Nilai * bobot matra
        }

        // Hitung skor total SLHD berdasarkan bobot BAB
        $total =
            ($row['Bab_1'] ?? 0) * $this->babWeights['Bab_1'] +
            $bab2Score * $this->babWeights['Bab_2'] +
            ($row['Bab_3'] ?? 0) * $this->babWeights['Bab_3'] +
            ($row['Bab_4'] ?? 0) * $this->babWeights['Bab_4'] +
            ($row['Bab_5'] ?? 0) * $this->babWeights['Bab_5'];

        return $total;
    }

    /**
     * Apakah row ini lolos threshold?
     */
    public function passesSLHD(array $row): bool
    {
        return $row['Total_Skor'] >= 60;  // threshold sesuai kebutuhanmu
    }
    
}
