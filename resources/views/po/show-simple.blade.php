@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>
                        <i class="fas fa-file-invoice"></i>
                        Purchase Order Detail: {{ $docNo }}
                    </h4>
                    <small>Simple View - Phase 2.2 Development</small>
                </div>
                <div class="card-body">
                    @if(isset($po))
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">PO Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="30%"><strong>PO Number:</strong></td>
                                        <td>{{ $po->DocNo }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td>{{ date('d/m/Y', strtotime($po->DateNo)) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Supplier:</strong></td>
                                        <td>{{ $po->SupName }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Net Amount:</strong></td>
                                        <td class="text-end"><strong>{{ number_format($po->NetAmount, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-primary">User Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="30%"><strong>User:</strong></td>
                                        <td>{{ $user->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td>{{ $user->username }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Role:</strong></td>
                                        <td><span class="badge bg-info">{{ ucfirst($user->role) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Approval Level:</strong></td>
                                        <td><span class="badge bg-success">{{ $user->approval_level }}</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Development Status</h5>
                            <p><strong>✅ Phase 2.2 Progress:</strong></p>
                            <ul>
                                <li>✅ Database Connection Working</li>
                                <li>✅ Authentication Working</li>
                                <li>✅ PO Data Retrieval Working</li>
                                <li>🔄 Full PO Detail View - In Development</li>
                                <li>🔄 Approval System - In Development</li>
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <strong>Next Steps:</strong><br>
                                    1. Complete PO Detail View<br>
                                    2. Add Approval Workflow<br>
                                    3. Add Notification System
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <strong>Available Actions:</strong><br>
                                    🔍 View PO Details<br>
                                    📋 Check Approval Status<br>
                                    🖨️ Print PO (Coming Soon)
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <h5>PO Data Not Available</h5>
                            <p>Unable to load purchase order data. Please check:</p>
                            <ul>
                                <li>PO Number exists in database</li>
                                <li>PO Number starts with 'PP'</li>
                                <li>Database connection is working</li>
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4">
                        <div class="btn-group" role="group">
                            <a href="{{ route('po.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to PO List
                            </a>
                            @if(isset($po))
                                <button class="btn btn-primary" disabled>
                                    <i class="fas fa-check"></i> Approve (Coming Soon)
                                </button>
                                <button class="btn btn-outline-primary" disabled>
                                    <i class="fas fa-print"></i> Print (Coming Soon)
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection