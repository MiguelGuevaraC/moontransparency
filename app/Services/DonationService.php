<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Donation;
use App\Models\Donation;

class DonationService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getDonationById(int $id): ?Donation
    {
        return Donation::find($id);
    }

    public function createDonation(array $data): Donation
    {
        $proyect = Donation::create($data);
        return $proyect;
    }
    
    public function updateDonation(Donation $proyect, array $data): Donation
    {
      $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return Donation::find($id)?->delete() ?? false;
    }

}
