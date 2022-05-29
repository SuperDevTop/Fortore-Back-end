<?php

namespace App\Imports;

use App\Models\User;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class FixIvestorDataImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection->whereNotNull(2) as $item) {
            $firstname = $this->cleanName($item->get(0));
            $lastname = $this->cleanName($item->get(1));
            try {
                $date = Carbon::parse($item->get(4))->format('m/d/Y');
            } catch (Exception $exception) {
                $date = $item->get(4);
            }
            User::where('username', $item->get(2))->update([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'birth_day' => $date
            ]);
        }
    }

    public function cleanName($str)
    {
        $pos = strpos($str, "=");
        if ($pos === false) return $str;
        return substr($str, 0, $pos);
    }
}
