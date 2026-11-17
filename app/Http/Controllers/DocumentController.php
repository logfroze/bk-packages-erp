<?php

namespace App\Http\Controllers;

use App\Models\DocumentSequence;
use App\Models\GateRegister;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Official Delivery Challan (Matching physical format & voice note specification).
     */
    public function deliveryChallan(Request $request, ?int $id = null): View
    {
        $gate = $id ? GateRegister::with('party')->find($id) : null;

        // Default or record data
        $challanNo = $gate?->bilti_challan_number ?: DocumentSequence::previewNextNumber('DELIVERY_CHALLAN');
        $date = $gate?->time_in ? $gate->time_in->format('d-m-Y') : now()->format('d-m-Y');
        $vehicleNo = $gate?->vehicle_number ?: ($request->get('vehicle_no') ?: 'CAM 7713');

        $partyName = $gate?->party?->name ?: ($request->get('party_name') ?: 'Ahmed Oil & Ghee Industry Ltd.');
        $partyAddress = $gate?->party?->address ?: ($request->get('party_address') ?: 'Shahbaz Pur Road Rahim Yar Khan');
        $partyPhone = $gate?->party?->phone ?: ($request->get('party_phone') ?: '685674169');

        // Parse items (default to sample or gate register)
        $items = [];
        if ($gate && $gate->total_pieces > 0) {
            $pcsPerBundle = $gate->total_bundles > 0 ? round($gate->total_pieces / $gate->total_bundles) : 10;
            $items[] = [
                'sr' => 1,
                'description' => $gate->material_summary ?: 'CARTON SHAHBAZ BANASPATI 1KG*12P',
                'bundles' => $gate->total_bundles ?: round($gate->total_pieces / 10),
                'pcs_per_bundle' => $pcsPerBundle,
                'total_qty' => $gate->total_pieces,
            ];
        } else {
            // Standard format matching uploaded reference image & voice note
            $items[] = [
                'sr' => 1,
                'description' => $request->get('item_name', 'CARTON SHAHBAZ BANASPATI 1KG*12P'),
                'bundles' => (int) $request->get('bundles', 1054),
                'pcs_per_bundle' => (int) $request->get('pcs_per_bundle', 10), // Can be 10 or 20 per bundle
                'total_qty' => (int) $request->get('total_qty', 10540),
            ];
        }

        $grandTotalQty = array_sum(array_column($items, 'total_qty'));

        return view('documents.delivery_challan', compact(
            'gate',
            'challanNo',
            'date',
            'vehicleNo',
            'partyName',
            'partyAddress',
            'partyPhone',
            'items',
            'grandTotalQty'
        ));
    }

    /**
     * Official Sales Invoice / Bill Receipt (Matching physical format reference).
     */
    public function salesInvoice(Request $request, ?int $id = null): View
    {
        $voucher = $id ? Voucher::with('party')->find($id) : null;

        $billNo = $voucher?->voucher_number ?: ($request->get('bill_no') ?: '80');
        $date = $voucher?->voucher_date ? $voucher->voucher_date->format('d-m-Y') : now()->format('d-m-Y');

        $partyName = $voucher?->party?->name ?: ($request->get('party_name') ?: 'Haji Faqeer Ghee Mills 7-KM Multan Road');
        $partyAddress = $voucher?->party?->address ?: ($request->get('party_address') ?: 'Burewala');
        $partyPhone = $voucher?->party?->phone ?: ($request->get('party_phone') ?: 'PH # 0673355586');

        $items = [];
        if ($voucher && $voucher->total_amount > 0) {
            $items[] = [
                'sr' => 1,
                'description' => $voucher->description ?: 'CARTON MASTER PACKAGING',
                'qty' => 5000,
                'rate' => round($voucher->total_amount / 5000, 2),
                'amount' => $voucher->total_amount,
            ];
        } else {
            // Standard format matching uploaded reference image
            $items = [
                [
                    'sr' => 1,
                    'description' => 'CARTON IBRAHIM B 1KG*10P',
                    'qty' => 5485,
                    'rate' => 93.50,
                    'amount' => 512848,
                ],
                [
                    'sr' => 2,
                    'description' => 'CARTON IBRAHIM SOAP 20 CAKE',
                    'qty' => 6548,
                    'rate' => 59.50,
                    'amount' => 389606,
                ],
            ];
        }

        $grandTotalAmount = array_sum(array_column($items, 'amount'));

        return view('documents.sales_invoice', compact(
            'voucher',
            'billNo',
            'date',
            'partyName',
            'partyAddress',
            'partyPhone',
            'items',
            'grandTotalAmount'
        ));
    }
}
