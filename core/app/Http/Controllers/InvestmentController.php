<?php

namespace App\Http\Controllers;

use App\Enums\WalletType;
use App\Http\Requests\StoreInvestmentRequest;
use App\Http\Requests\ValidateInvestApproveRequest;
use App\Models\GeneralSetting;
use App\Models\Holiday;
use App\Models\Invest;
use App\Repositories\InvestmentRepository;
use App\Repositories\WalletRepository;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mpdf\MpdfException;

class InvestmentController extends Controller
{
    private $walletRepo;
    private $activeTemplate;
    private $investmentRepo;

    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
        $this->walletRepo = new WalletRepository();
        $this->investmentRepo = new InvestmentRepository();
    }

    public function createPlanInvestment(StoreInvestmentRequest $request): RedirectResponse
    {
        $gnl = GeneralSetting::getActiveSetting();
        $plan = $request->getPlan();

        if ($request->isWalletType("checkout")) {
            session()->put('amount', encrypt($request->getAmount()));
            session()->put('token', encrypt($plan->id));
            return redirect()->route('user.deposit');
        }
        $walletType = WalletType::from($request->getWalletType());
        $now = Carbon::now();
        $offDay = (array)$gnl->off_day;
        while (true) {
            $nextPossible = Carbon::parse($now)->addHours($plan->times)->toDateTimeString();
            $dayName = strtolower(date('D', strtotime($nextPossible)));
            $holiday = Holiday::where('date', date('Y-m-d', strtotime($nextPossible)))->count();
            if (!array_key_exists($dayName, $offDay)) {
                if ($holiday == 0) {
                    $next = $nextPossible;
                    break;
                }
            }
            $now = $nextPossible;
        }
        $invest =  $this->investmentRepo->createInvestment($plan, auth()->user(), $gnl, $request->getAmount(), $next, $walletType);
        return redirect()->route('user.invest.contract', $invest->id);

    }

    /**
     * @throws MpdfException
     */
    public function contract(Invest $invest)
    {
        if ($invest->hasSignedByUser()) {
            abort(404);
        }
        $page_title = $invest->trx;
        $user = Auth::user();
        $pdfUrl = Storage::url($this->investmentRepo->toPdf($invest));
        $this->investmentRepo->sendContractConfirmationOtp($invest);
        return view($this->activeTemplate . 'user.invest_contract', compact('invest', 'user', 'page_title', 'pdfUrl'));
    }


    /**
     * @throws ValidationException
     * @throws MpdfException
     */
    public function approveContract(Invest $invest, ValidateInvestApproveRequest $request)
    {
        if ($invest->hasSignedByUser()) {
            abort(404);
        }
        $request->validatePinCode($invest);
        $this->investmentRepo->hasValidBalance($invest);
        $investment =  $this->investmentRepo->approveInvestment($invest);
        if($investment)
        {
            $notify[] = ['success', 'Invested Successfully'];
            return redirect()->route('user.interest.log')->withNotify($notify);
        }
        return back()->withNotify(['error', 'Try Later ']);
    }


}
