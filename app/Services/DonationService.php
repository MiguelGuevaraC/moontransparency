<?php
namespace App\Services;

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

        $data['imagesave'] = isset($data['images']) ? $data['images'] : null;
        $data['images']    = null;
        $donation          = Donation::create($data);
       
        if ($donation) {
            // $this->commonService->store_photo($data, $donation, name_folder: 'donations');
        }
 
        return $donation;
    }

    public function updateDonation(Donation $donation, array $data): Donation
    {
        $data['imagesave'] = isset($data['images']) ? $data['images'] : null;
        // $this->commonService->update_photo($data, $donation, 'donations');
        $donation->update($data);
        return $donation;
    }

    public function destroyById($id)
    {
        return Donation::find($id)?->delete() ?? false;
    }

}
