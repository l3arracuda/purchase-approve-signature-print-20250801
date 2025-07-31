@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('Purchase System Dashboard') }}</h4>
                    <small class="text-muted">Welcome, {{ $user->full_name }} ({{ ucfirst($user->role) }})</small>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- User Info & Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>User Role</h5>
                                    <h2>{{ ucfirst($user->role) }}</h2>
                                    <small>Approval Level: {{ $user->approval_level }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Available POs</h5>
                                    <h2>{{ $stats['total_pos'] }}</h2>
                                    <small>Ready for Process</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Connection Status</h5>
                                    <h2>✓ Online</h2>
                                    <small>Both Databases</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>System</h5>
                                    <h2>Ready</h2>
                                    <small>Phase 1.2 Complete</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent POs Preview -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Purchase Orders (PP% only)</h5>
                        </div>
                        <div class="card-body">
                            @if(count($recentPOs) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>PO Number</th>
                                                <th>Supplier</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentPOs as $po)
                                            <tr>
                                                <td>{{ date('d/m/Y', strtotime($po->DateNo)) }}</td>
                                                <td><strong>{{ $po->DocNo }}</strong></td>
                                                <td>{{ $po->SupName }}</td>
                                                <td class="text-end">{{ number_format($po->NetAmout, 2) }}</td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $po->AppStatus }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    No Purchase Orders found or database connection issue.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4">
                        <h5>Quick Actions</h5>
                        <div class="btn-group" role="group">
                            @if($user->approval_level >= 1)
                                <a href="#" class="btn btn-outline-primary">View All POs</a>
                            @endif
                            @if($user->approval_level >= 2)
                                <a href="#" class="btn btn-outline-success">Pending Approvals</a>
                            @endif
                            @if($user->isAdmin())
                                <a href="#" class="btn btn-outline-warning">User Management</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection