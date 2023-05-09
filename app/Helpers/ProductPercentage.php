<?php
/**
 * @author: pujiermanto@gmail.com
 * @param For Percentage Products with Relationship Table
 * */

namespace App\Helpers;

use App\Models\Product;

class ProductPercentage
{
    public function getPercentage($category, $totals)
    {
        switch($category) {
            case 'Apel':
                $Apel = Product::whereNull('deleted_at')
                ->with('categories')
                ->whereRelation('categories', 'name', 'like', '%'.$category.'%')
                ->get()
                ->toArray();
                $countApel = count($Apel);
                $apelPercent = ($totals * $countApel) / 100;
                return $apelPercent > null ? $apelPercent : 0;
            break;

            case 'Anggur':
                $Anggur = Product::whereNull('deleted_at')
                    ->with('categories')
                    ->whereRelation('categories', 'name', 'like', '%'.$category.'%')
                    ->get()
                    ->toArray();
                $countAnggur = count($Anggur);
                $anggurPercent = ($totals * $countAnggur) / 100;
                return $anggurPercent > null ? $anggurPercent : 0;
            break;

            case 'Jeruk':
                $Jeruk = Product::whereNull('deleted_at')
                    ->with('categories')
                    ->whereRelation('categories', 'name', 'like', '%'.$category.'%')
                    ->get()
                    ->toArray();
                $countJeruk = count($Jeruk);
                $jerukPercent = ($totals * $countJeruk) / 100;
                return $jerukPercent > null ? $jerukPercent : 0;
            break;

            case 'Pear':
                $Pear = Product::whereNull('deleted_at')
                    ->with('categories')
                    ->whereRelation('categories', 'name', 'like', '%'.$category.'%')
                    ->get()
                    ->toArray();
                $countPear = count($Pear);
                $pearPercent = ($totals * $countPear) / 100;
                return $pearPercent > null ? $pearPercent : 0;
            break;

            case 'Lengkeng':
                $Lengkeng = Product::whereNull('deleted_at')
                    ->with('categories')
                    ->whereRelation('categories', 'name', 'like', '%'.$category.'%')
                    ->get()
                    ->toArray();
                $countLengkeng = count($Lengkeng);
                $lengkengPercent = ($totals * $countLengkeng) / 100;
                return $lengkengPercent > null ? $lengkengPercent : 0;
            break;

            default:
                return 0;
        }
    }
}
