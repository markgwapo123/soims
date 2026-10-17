<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRisRequest;
use App\Http\Requests\ReleaseRisRequest;
use App\Models\Ris;
use App\Models\SupplyRequest;
use App\Services\RisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RisController extends Controller
{
    public function __construct(private readonly RisService $ris) {}

    public function index(): View
    {
        return view('ris.index', [
            'awaiting' => SupplyRequest::with(['requestor', 'department'])->withCount('items')
                ->where('status', SupplyRequest::STATUS_CONFIRMED)->latest('confirmed_at')->get(),
            'documents' => Ris::with(['supplyRequest', 'issuer'])->withCount('items')->latest()->paginate(15),
        ]);
    }

    public function create(SupplyRequest $supplyRequest): View
    {
        abort_unless($supplyRequest->status === SupplyRequest::STATUS_CONFIRMED, 404);

        return view('ris.create', [
            'supplyRequest' => $supplyRequest->load(['requestor', 'department']),
            'lines' => $this->ris->lines($supplyRequest),
        ]);
    }

    public function store(StoreRisRequest $request, SupplyRequest $supplyRequest): RedirectResponse
    {
        $ris = $this->ris->prepare($supplyRequest, $request->user(), $request->validated(), $request->ip(), $request->userAgent());

        return redirect()->route('ris.show', $ris)->with('status', 'RIS prepared.');
    }

    public function release(ReleaseRisRequest $request, Ris $ris): RedirectResponse
    {
        $this->ris->release($ris, $request->user(), $request->validated(), $request->ip(), $request->userAgent());

        return redirect()->route('ris.show', $ris)->with('status', 'RIS released and stock updated.');
    }

    public function show(Ris $ris): View
    {
        return view('ris.show', ['ris' => $ris->load(['items', 'supplyRequest', 'requester', 'approver', 'issuer', 'release.releaser', 'release.receiver'])]);
    }
}
