<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// ทดสอบการเชื่อมต่อ Modern Database
Route::get('/test-modern-db', function() {
    try {
        $result = DB::connection('modern')->select('SELECT @@VERSION as version');
        return response()->json([
            'status' => 'success',
            'database' => 'modern',
            'version' => $result[0]->version
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'modern',
            'message' => $e->getMessage()
        ]);
    }
});

// ทดสอบการเชื่อมต่อ Legacy Database
Route::get('/test-legacy-db', function() {
    try {
        $result = DB::connection('legacy')->select('SELECT @@VERSION as version');
        return response()->json([
            'status' => 'success',
            'database' => 'legacy',
            'version' => $result[0]->version
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'legacy',
            'message' => $e->getMessage()
        ]);
    }
});

// ทดสอบดึงข้อมูล PO จาก Legacy Database
Route::get('/test-po-data', function() {
    try {
        $pos = DB::connection('legacy')->select("
            SELECT TOP 5 
                h.DOCDAT as DateNo, 
                h.DOCNO as DocNo, 
                h.RefPoNo as DocRef, 
                h.SUPCD as SupNo,
                s.SUPNAM as SupName, 
                h.NETAMT as NetAmout
            FROM [Romar1].[dbo].[POC_POH] h
            JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
            WHERE h.APPSTS <> 'C'
            ORDER BY h.DOCDAT DESC
        ");
        
        return response()->json([
            'status' => 'success',
            'count' => count($pos),
            'data' => $pos
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});