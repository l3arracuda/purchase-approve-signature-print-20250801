<?php
// app/Http/Controllers/SignatureController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\UserSignature;

class SignatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * แสดงหน้าจัดการ Digital Signature
     */
    public function manage()
    {
        $user = Auth::user();
        
        // ดึงลายเซ็นปัจจุบันที่ active
        $activeSignature = $user->getActiveSignature();
        
        // ดึงประวัติลายเซ็นทั้งหมด
        $signatures = $user->signatures()->orderBy('is_active', 'desc')->orderBy('created_at', 'desc')->get();
        
        return view('signature.manage', compact('activeSignature', 'signatures'));
    }

    /**
     * Upload Digital Signature
     */
    public function upload(Request $request)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg|max:1024', // 1MB max
            'signature_name' => 'required|string|max:100',
        ], [
            'signature.required' => 'Please select a signature image',
            'signature.image' => 'The file must be an image',
            'signature.mimes' => 'Only PNG, JPG, and JPEG formats are allowed',
            'signature.max' => 'The image size must not exceed 1MB',
            'signature_name.required' => 'Please enter a signature name',
            'signature_name.max' => 'Signature name must not exceed 100 characters',
        ]);

        try {
            DB::connection('modern')->beginTransaction();
            
            $user = Auth::user();

            // สร้าง folder signatures ถ้าไม่มี
            if (!Storage::disk('public')->exists('signatures')) {
                Storage::disk('public')->makeDirectory('signatures');
            }

            // สร้างชื่อไฟล์ที่ unique
            $fileName = 'signature_' . $user->id . '_' . time() . '.' . $request->file('signature')->getClientOriginalExtension();
            
            // Save file
            $path = $request->file('signature')->storeAs('signatures', $fileName, 'public');

            // เก็บ base64 data (optional)
            $signatureData = base64_encode(file_get_contents($request->file('signature')));

            // สร้าง signature record ใหม่ (จะ deactivate อันเก่าอัตโนมัติ)
            UserSignature::createUserSignature(
                $user->id,
                $request->signature_name,
                $path,
                $signatureData
            );

            DB::connection('modern')->commit();

            \Log::info('Signature uploaded successfully', [
                'user_id' => $user->id,
                'signature_name' => $request->signature_name,
                'file_path' => $path
            ]);

            return back()->with('success', 'Digital signature uploaded successfully!');

        } catch (\Exception $e) {
            DB::connection('modern')->rollBack();
            
            // ลบไฟล์ถ้าอัปโหลดแล้วแต่ database error
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            
            \Log::error('Signature upload error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error uploading signature: ' . $e->getMessage()]);
        }
    }

    /**
     * Activate Signature
     */
    public function activate(Request $request, $signatureId)
    {
        try {
            $user = Auth::user();
            $signature = UserSignature::where('id', $signatureId)->where('user_id', $user->id)->first();

            if (!$signature) {
                return response()->json(['success' => false, 'message' => 'Signature not found'], 404);
            }

            DB::connection('modern')->beginTransaction();

            // Deactivate all user signatures
            UserSignature::where('user_id', $user->id)->update(['is_active' => false]);

            // Activate selected signature
            $signature->update(['is_active' => true]);

            DB::connection('modern')->commit();

            \Log::info('Signature activated', [
                'user_id' => $user->id,
                'signature_id' => $signatureId,
                'signature_name' => $signature->signature_name
            ]);

            return response()->json(['success' => true, 'message' => 'Signature activated successfully']);

        } catch (\Exception $e) {
            DB::connection('modern')->rollBack();
            \Log::error('Signature activation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error activating signature'], 500);
        }
    }

    /**
     * Deactivate Signature
     */
    public function deactivate(Request $request, $signatureId)
    {
        try {
            $user = Auth::user();
            $signature = UserSignature::where('id', $signatureId)->where('user_id', $user->id)->first();

            if (!$signature) {
                return response()->json(['success' => false, 'message' => 'Signature not found'], 404);
            }

            $signature->update(['is_active' => false]);

            \Log::info('Signature deactivated', [
                'user_id' => $user->id,
                'signature_id' => $signatureId,
                'signature_name' => $signature->signature_name
            ]);

            return response()->json(['success' => true, 'message' => 'Signature deactivated successfully']);

        } catch (\Exception $e) {
            \Log::error('Signature deactivation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deactivating signature'], 500);
        }
    }

    /**
     * Delete Signature
     */
    public function delete(Request $request, $signatureId)
    {
        try {
            $user = Auth::user();
            $signature = UserSignature::where('id', $signatureId)->where('user_id', $user->id)->first();

            if (!$signature) {
                return response()->json(['success' => false, 'message' => 'Signature not found'], 404);
            }

            // Delete file
            if ($signature->signature_path && Storage::disk('public')->exists($signature->signature_path)) {
                Storage::disk('public')->delete($signature->signature_path);
                \Log::info('Signature file deleted', ['path' => $signature->signature_path]);
            }

            // Delete record
            $signatureName = $signature->signature_name;
            $signature->delete();

            \Log::info('Signature deleted', [
                'user_id' => $user->id,
                'signature_id' => $signatureId,
                'signature_name' => $signatureName
            ]);

            return response()->json(['success' => true, 'message' => 'Signature deleted successfully']);

        } catch (\Exception $e) {
            \Log::error('Signature deletion error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting signature'], 500);
        }
    }

    /**
     * Get User's Active Signature (API for other components)
     */
    public function getActiveSignature()
    {
        $user = Auth::user();
        $signature = $user->getActiveSignature();

        if ($signature) {
            return response()->json([
                'success' => true,
                'signature' => [
                    'id' => $signature->id,
                    'name' => $signature->signature_name,
                    'url' => $signature->signature_url,
                    'path' => $signature->signature_path,
                    'created_at' => $signature->created_at->format('d/m/Y H:i'),
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No active signature found'], 404);
    }

    /**
     * Check if user has active signature (for approval validation)
     */
    public function hasActiveSignature()
    {
        $user = Auth::user();
        $hasSignature = $user->hasActiveSignature();

        return response()->json([
            'success' => true,
            'has_signature' => $hasSignature,
            'message' => $hasSignature ? 'User has active signature' : 'No active signature found'
        ]);
    }
}