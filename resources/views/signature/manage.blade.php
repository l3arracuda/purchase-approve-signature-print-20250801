{{-- resources/views/signature/manage.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-signature"></i>
                        Digital Signature Management
                    </h4>
                    <small class="text-muted">Manage your digital signatures for PO approvals</small>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Current Active Signature -->
                    @if($activeSignature)
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-circle"></i> Current Active Signature
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            @if($activeSignature->signature_url)
                                                <img src="{{ $activeSignature->signature_url }}" 
                                                     alt="{{ $activeSignature->signature_name }}" 
                                                     class="img-thumbnail bg-white"
                                                     style="max-height: 120px; max-width: 200px;">
                                            @else
                                                <div class="bg-light border rounded p-3">
                                                    <i class="fas fa-image fa-2x text-muted"></i>
                                                    <p class="text-muted small mb-0">Image not available</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <h6><strong>{{ $activeSignature->signature_name }}</strong></h6>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar"></i>
                                            <strong>Uploaded:</strong> {{ $activeSignature->created_at->format('d/m/Y H:i') }}
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-user"></i>
                                            <strong>Owner:</strong> {{ Auth::user()->full_name }}
                                        </p>
                                        <p class="mb-3">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Status:</strong> 
                                            <span class="badge bg-success">Active</span>
                                        </p>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#signatureModal">
                                                <i class="fas fa-upload"></i> Upload New Signature
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deactivateSignature({{ $activeSignature->id }})">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> No Active Signature</h5>
                            <p>You don't have an active digital signature. You need to upload one to approve purchase orders.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#signatureModal">
                                <i class="fas fa-upload"></i> Upload Your First Signature
                            </button>
                        </div>
                    @endif

                    <!-- Signature History -->
                    @if($signatures->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-history"></i> Signature History
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="15%">Preview</th>
                                                <th width="30%">Name</th>
                                                <th width="20%">Upload Date</th>
                                                <th width="15%">Status</th>
                                                <th width="20%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($signatures as $signature)
                                            <tr class="{{ $signature->is_active ? 'table-success' : '' }}">
                                                <td>
                                                    @if($signature->signature_url)
                                                        <img src="{{ $signature->signature_url }}" 
                                                             alt="{{ $signature->signature_name }}" 
                                                             class="img-thumbnail"
                                                             style="max-height: 40px; max-width: 80px;">
                                                    @else
                                                        <i class="fas fa-image text-muted"></i>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ $signature->signature_name }}</strong>
                                                    @if($signature->is_active)
                                                        <br><small class="text-success"><i class="fas fa-star"></i> Current Active</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $signature->created_at->format('d/m/Y H:i') }}
                                                    <br><small class="text-muted">{{ $signature->created_at->diffForHumans() }}</small>
                                                </td>
                                                <td>
                                                    @if($signature->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        @if(!$signature->is_active)
                                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="activateSignature({{ $signature->id }})">
                                                                <i class="fas fa-check"></i> Activate
                                                            </button>
                                                        @endif
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteSignature({{ $signature->id }})">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Usage Instructions -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle"></i> How to Use Digital Signatures
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-upload"></i> Uploading Signatures</h6>
                                    <ul class="small">
                                        <li>Only PNG, JPG, and JPEG formats are supported</li>
                                        <li>Maximum file size is 1MB</li>
                                        <li>Transparent PNG files work best</li>
                                        <li>Only one signature can be active at a time</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-file-signature"></i> Using in Approvals</h6>
                                    <ul class="small">
                                        <li>Your active signature appears on approved PO documents</li>
                                        <li>You must have an active signature to approve POs</li>
                                        <li>Signatures are automatically added to PDF exports</li>
                                        <li>Each approval level can have different signatures</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Signature Modal Component -->
@include('components.signature-modal')

<!-- JavaScript for Actions -->
<script>
function activateSignature(signatureId) {
    if (confirm('Are you sure you want to activate this signature? This will deactivate your current signature.')) {
        fetch(`/signature/${signatureId}/activate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while activating the signature.');
        });
    }
}

function deactivateSignature(signatureId) {
    if (confirm('Are you sure you want to deactivate this signature? You will not be able to approve POs until you activate another signature.')) {
        fetch(`/signature/${signatureId}/deactivate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deactivating the signature.');
        });
    }
}

function deleteSignature(signatureId) {
    if (confirm('Are you sure you want to delete this signature? This action cannot be undone.')) {
        fetch(`/signature/${signatureId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the signature.');
        });
    }
}
</script>
@endsection