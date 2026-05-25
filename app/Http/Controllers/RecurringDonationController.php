<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Services\DonationService;
use App\Models\RecurringDonation;
use Illuminate\Http\Request;
use App\Models\Project;

class RecurringDonationController extends Controller
{
    protected $donationService;
    public function __construct(DonationService $donationService)
    {
        $this->donationService = $donationService;
    }
    private function getNextDonationDate($frequency){
        $currentDate = now();
        switch ($frequency) {
            case 'daily':
                return $currentDate->addDay();
            case 'weekly':
                return $currentDate->addWeek();
            case 'monthly':
                return $currentDate->addMonth();
            default:
                throw new \Exception('Invalid frequency');
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:daily,weekly,monthly',
        ]);
        $user = $request->user();
        $project = Project::findOrFail($request->project_id);
        if(!$project->isActive()){
            return response()->json([
                'message'=>'Cannot set up recurring donation for an inactive project'
                ], 400);
        }
        $amount = $request->amount;
        try{
            $this->donationService->executeDonation($user, $project, $amount);

        }catch (\Exception $e){
            return response()->json([
                'message'=>$e->getMessage()
            ],400);
        }

        $recurringDonation = $user->recurringDonations()->create([
            'project_id' => $request->project_id,
            'amount' => $request->amount,
            'frequency' => $request->frequency,
            'next_donation_date' => $this->getNextDonationDate($request->frequency),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Recurring donation created successfully',
            'data' => $recurringDonation
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(RecurringDonation $recurringDonation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecurringDonation $recurringDonation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RecurringDonation $recurringDonation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringDonation $recurringDonation)
    {
        //
    }
}
