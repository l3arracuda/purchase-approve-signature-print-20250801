{{-- resources/views/components/signature-modal.blade.php --}}

<!-- Digital Signature Upload Modal -->
<div class="modal fade" id="signatureModal" tabindex="-1" aria-labelledby="signatureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signatureModalLabel">
                    <i class="fas fa-signature"></i> Upload Digital Signature
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('signature.upload') }}" method="POST" enctype="multipart/form-data" id="signatureUploadForm">
                @csrf
                <div class="modal-body">
                    <!-- Current Signature Display -->
                    @if(Auth::user()->hasActiveSignature())
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Current Active Signature</h6>
                            <div class="row">
                                <div class="col-md-8">
                                    <strong>{{ Auth::user()->getActiveSignature()->signature_name }}</strong><br>
                                    <small class="text-muted">
                                        Uploaded: {{ Auth::user()->getActiveSignature()->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    @if(Auth::user()->getSignatureUrl())
                                        <img src="{{ Auth::user()->getSignatureUrl() }}" 
                                             alt="Current Signature" 
                                             class="img-thumbnail" 
                                             style="max-height: 60px;">
                                    @endif
                                </div>
                            </div>
                            <small class="text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Uploading a new signature will deactivate the current one.
                            </small>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>No Digital Signature Found</strong><br>
                            You need to upload a digital signature to approve purchase orders.
                        </div>
                    @endif

                    <!-- Upload Form -->
                    <div class="mb-3">
                        <label for="signature_name" class="form-label">
                            <i class="fas fa-tag"></i> Signature Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="signature_name" 
                               id="signature_name"
                               placeholder="e.g., John Doe Official Signature"
                               required
                               maxlength="100">
                        <div class="form-text">Give your signature a descriptive name</div>
                    </div>

                    <div class="mb-3">
                        <label for="signature_file" class="form-label">
                            <i class="fas fa-upload"></i> Signature Image <span class="text-danger">*</span>
                        </label>
                        <input type="file" 
                               class="form-control" 
                               name="signature" 
                               id="signature_file"
                               accept="image/png,image/jpg,image/jpeg"
                               required>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i>
                            Supported formats: PNG, JPG, JPEG | Max size: 1MB | Recommended: Transparent PNG
                        </div>
                    </div>

                    <!-- Preview Area -->
                    <div class="mb-3" id="signaturePreviewArea" style="display: none;">
                        <label class="form-label"><i class="fas fa-eye"></i> Preview</label>
                        <div class="border rounded p-3 text-center bg-light">
                            <img id="signaturePreview" src="" alt="Signature Preview" class="img-fluid" style="max-height: 100px;">
                        </div>
                    </div>

                    <!-- Guidelines -->
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-lightbulb"></i> Signature Guidelines</h6>
                            <ul class="small mb-0">
                                <li>Use a clear, professional signature image</li>
                                <li>Transparent PNG format works best</li>
                                <li>Avoid backgrounds or watermarks</li>
                                <li>Ensure the signature is legible when printed</li>
                                <li>This signature will appear on approved PO documents</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="uploadSignatureBtn">
                        <i class="fas fa-upload"></i> Upload Signature
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Preview -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('signature_file');
    const preview = document.getElementById('signaturePreview');
    const previewArea = document.getElementById('signaturePreviewArea');
    const uploadBtn = document.getElementById('uploadSignatureBtn');
    const form = document.getElementById('signatureUploadForm');

    // File preview
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Validate file size (1MB = 1048576 bytes)
            if (file.size > 1048576) {
                alert('File size must be less than 1MB');
                fileInput.value = '';
                previewArea.style.display = 'none';
                return;
            }

            // Validate file type
            const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only PNG, JPG, and JPEG files are allowed');
                fileInput.value = '';
                previewArea.style.display = 'none';
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewArea.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewArea.style.display = 'none';
        }
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        uploadBtn.disabled = true;
    });

    // Reset form when modal is closed
    document.getElementById('signatureModal').addEventListener('hidden.bs.modal', function() {
        form.reset();
        previewArea.style.display = 'none';
        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Signature';
        uploadBtn.disabled = false;
    });
});
</script>