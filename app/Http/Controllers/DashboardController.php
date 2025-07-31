<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // ดึงข้อมูล PO เบื้องต้นจาก Legacy Database (เฉพาะ PP%)
        $poQuery = "
            SELECT TOP 10 
                h.DOCDAT as DateNo, 
                h.DOCNO as DocNo, 
                h.RefPoNo as DocRef, 
                h.SUPCD as SupNo,
                s.SUPNAM as SupName, 
                h.NETAMT as NetAmout,
                h.APPSTS as AppStatus
            FROM [Romar1].[dbo].[POC_POH] h
            JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
            JOIN [Romar1].[dbo].[POC_POD] d ON h.DOCNO = d.DOCNO
            JOIN [Romar1].[dbo].[INV_PDT] i on d.PDTCD = i.PDTCD
            WHERE i.PDTTYP = '1' and h.APPSTS <> 'C' AND h.DOCNO LIKE 'PP%'
            GROUP BY h.DOCDAT, h.DOCNO, h.RefPoNo, h.SUPCD, s.SUPNAM, h.NETAMT, h.APPSTS
            ORDER BY h.DOCNO DESC
        ";

        try {
            $recentPOs = DB::connection('legacy')->select($poQuery);
        } catch (\Exception $e) {
            $recentPOs = [];
        }

        // สถิติเบื้องต้น
        $stats = [
            'total_pos' => count($recentPOs),
            'user_role' => $user->role,
            'approval_level' => $user->approval_level,
        ];

        return view('dashboard', compact('user', 'recentPOs', 'stats'));
    }
}