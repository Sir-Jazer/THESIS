<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">QR Scanner</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Slot Selection --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-slate-800">Select Assignment</h3>

                @if ($sections->isEmpty())
                    <p class="text-sm text-slate-500">You have no published assignments yet.</p>
                @else
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="section-select">Section</label>
                            <select id="section-select" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">— Select section —</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}">{{ $section->section_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="slot-select">Exam Slot</label>
                            <select id="slot-select" class="w-full rounded-md border-gray-300 text-sm" disabled>
                                <option value="">— Select slot —</option>
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Camera Scanner --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4" id="scanner-panel" style="display:none">
                <h3 class="font-semibold text-slate-800">Scan Student QR Code</h3>

                <div id="result-banner" class="hidden rounded-md px-4 py-3 text-sm font-medium"></div>

                <div class="flex flex-col items-center gap-3">
                    <video id="qr-video" class="w-full max-w-sm rounded-md border border-slate-200 bg-black" autoplay muted playsinline></video>
                    <canvas id="qr-canvas" class="hidden"></canvas>

                    <div class="flex gap-3">
                        <button id="start-btn" type="button"
                            class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
                            Start Camera
                        </button>
                        <button id="stop-btn" type="button"
                            class="hidden px-4 py-2 rounded-md bg-slate-500 text-white text-sm font-semibold hover:bg-slate-600 transition">
                            Stop Camera
                        </button>
                    </div>
                </div>
            </div>

            <div id="scan-confirm-modal" class="hidden fixed inset-0 z-50">
                <div class="absolute inset-0 bg-slate-900/50"></div>
                <div class="relative min-h-full flex items-center justify-center p-4">
                    <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h4 class="text-base font-semibold text-slate-800">Confirm Student Before Logging</h4>
                            <p class="mt-1 text-sm text-slate-500">Verify the details below before recording attendance.</p>
                        </div>
                        <div class="space-y-3 px-5 py-4 text-sm text-slate-700">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Student Name</p>
                                    <p id="confirm-student-name" class="font-semibold text-slate-900">-</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Student ID</p>
                                    <p id="confirm-student-id" class="font-semibold text-slate-900">-</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Section</p>
                                    <p id="confirm-section" class="font-semibold text-slate-900">-</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subject</p>
                                    <p id="confirm-subject" class="font-semibold text-slate-900">-</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Exam Slot</p>
                                <p id="confirm-slot-label" class="font-semibold text-slate-900">-</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Exam Reference Numbers</p>
                                <p id="confirm-exam-refs" class="font-semibold text-slate-900">-</p>
                            </div>
                            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2">
                                <p id="confirm-permit-message" class="font-medium text-emerald-700">Permit is valid for the current exam period.</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                            <button id="confirm-return-btn" type="button"
                                class="px-4 py-2 rounded-md bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200 transition">
                                Return to Scanner
                            </button>
                            <button id="confirm-log-btn" type="button"
                                class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
                                Log Student
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
        const slotOptions = @json($slotOptions);
        const previewUrl = "{{ route('proctor.scanner.preview') }}";
        const scanUrl = "{{ route('proctor.scanner.scan') }}";
        const csrfToken = "{{ csrf_token() }}";

        const sectionSelect = document.getElementById('section-select');
        const slotSelect = document.getElementById('slot-select');
        const scannerPanel = document.getElementById('scanner-panel');
        const video = document.getElementById('qr-video');
        const canvas = document.getElementById('qr-canvas');
        const startBtn = document.getElementById('start-btn');
        const stopBtn = document.getElementById('stop-btn');
        const resultBanner = document.getElementById('result-banner');
        const confirmModal = document.getElementById('scan-confirm-modal');
        const confirmStudentName = document.getElementById('confirm-student-name');
        const confirmStudentId = document.getElementById('confirm-student-id');
        const confirmSection = document.getElementById('confirm-section');
        const confirmSubject = document.getElementById('confirm-subject');
        const confirmSlotLabel = document.getElementById('confirm-slot-label');
        const confirmExamRefs = document.getElementById('confirm-exam-refs');
        const confirmPermitMessage = document.getElementById('confirm-permit-message');
        const confirmReturnBtn = document.getElementById('confirm-return-btn');
        const confirmLogBtn = document.getElementById('confirm-log-btn');

        let stream = null;
        let animFrame = null;
        let scanning = false;
        let scanPaused = false;
        let lastToken = null;
        let cooldownUntil = 0;
        let requestInFlight = false;
        let pendingToken = null;

        // Populate slot dropdown on section change
        sectionSelect?.addEventListener('change', () => {
            const sectionId = sectionSelect.value;
            slotSelect.innerHTML = '<option value="">— Select slot —</option>';

            if (!sectionId || !slotOptions[sectionId]) {
                slotSelect.disabled = true;
                scannerPanel.style.display = 'none';
                closeConfirmModal(false);
                stopCamera();
                return;
            }

            slotOptions[sectionId].forEach(slot => {
                const opt = document.createElement('option');
                opt.value = slot.id;
                opt.textContent = slot.label;
                slotSelect.appendChild(opt);
            });

            slotSelect.disabled = false;
            scannerPanel.style.display = 'none';
            closeConfirmModal(false);
            stopCamera();
        });

        slotSelect?.addEventListener('change', () => {
            if (slotSelect.value) {
                scannerPanel.style.display = 'block';
                clearBanner();
                closeConfirmModal(false);
            } else {
                scannerPanel.style.display = 'none';
                closeConfirmModal(false);
                stopCamera();
            }
        });

        startBtn?.addEventListener('click', startCamera);
        stopBtn?.addEventListener('click', stopCamera);
        confirmReturnBtn?.addEventListener('click', () => {
            closeConfirmModal(true);
        });
        confirmLogBtn?.addEventListener('click', submitConfirmedScan);

        async function startCamera() {
            clearBanner();
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                video.play();
                scanning = true;
                scanPaused = false;
                startBtn.classList.add('hidden');
                stopBtn.classList.remove('hidden');
                requestAnimationFrame(scanFrame);
            } catch (err) {
                showBanner('error', 'Could not access camera: ' + err.message);
            }
        }

        function stopCamera() {
            scanning = false;
            scanPaused = false;
            requestInFlight = false;
            closeConfirmModal(false);
            if (animFrame) {
                cancelAnimationFrame(animFrame);
                animFrame = null;
            }
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
            }
            video.srcObject = null;
            startBtn?.classList.remove('hidden');
            stopBtn?.classList.add('hidden');
        }

        function scanFrame() {
            if (!scanning) return;

            if (scanPaused) {
                animFrame = requestAnimationFrame(scanFrame);
                return;
            }

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });

                if (code && code.data) {
                    const now = Date.now();
                    if (code.data !== lastToken || now > cooldownUntil) {
                        lastToken = code.data;
                        cooldownUntil = now + 3000;
                        handleScan(code.data);
                    }
                }
            }

            animFrame = requestAnimationFrame(scanFrame);
        }

        function handleScan(rawData) {
            if (requestInFlight) {
                return;
            }

            const slotId = slotSelect.value;
            if (!slotId) {
                showBanner('error', 'Please select an exam slot before scanning.');
                return;
            }

            // Attempt to parse QR payload (JSON with token field, or raw token string)
            let token = rawData;
            try {
                const parsed = JSON.parse(rawData);
                if (parsed.token) token = parsed.token;
            } catch (_) {
                // use rawData as-is
            }

            requestInFlight = true;
            scanPaused = true;

            fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ slot_id: slotId, qr_token: token }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    pendingToken = token;
                    openConfirmModal(data);
                } else {
                    scanPaused = false;
                    showBanner('error', '✗ ' + (data.message || data.errors?.qr_token?.[0] || 'Scan failed.'));
                }
            })
            .catch(() => {
                scanPaused = false;
                showBanner('error', 'Network error. Please try again.');
            })
            .finally(() => {
                requestInFlight = false;
            });
        }

        function openConfirmModal(data) {
            const selectedSlot = slotSelect.options[slotSelect.selectedIndex];
            const selectedSectionId = sectionSelect.value;
            const selectedSlotMeta = (slotOptions[selectedSectionId] || [])
                .find(option => String(option.id) === String(slotSelect.value)) || {};

            confirmStudentName.textContent = data.student_name || '-';
            confirmStudentId.textContent = data.student_id || '-';
            confirmSection.textContent = data.section_code || selectedSlotMeta.section_code || '-';

            const subjectCode = data.subject_code || selectedSlotMeta.subject_code || '';
            const subjectName = data.subject_name || selectedSlotMeta.subject_name || '';
            if (subjectCode && subjectName) {
                confirmSubject.textContent = `${subjectCode} - ${subjectName}`;
            } else if (subjectCode) {
                confirmSubject.textContent = subjectCode;
            } else if (subjectName) {
                confirmSubject.textContent = subjectName;
            } else {
                confirmSubject.textContent = 'TBA';
            }

            confirmSlotLabel.textContent = selectedSlot?.textContent || '-';

            const refs = Array.isArray(data.exam_references) ? data.exam_references : [];
            confirmExamRefs.textContent = refs.length > 0 ? refs.join(', ') : 'None set';

            confirmPermitMessage.textContent = data.permit_message || 'Permit is valid for the current exam period.';
            confirmModal.classList.remove('hidden');
        }

        function closeConfirmModal(resumeScanning = true) {
            pendingToken = null;
            confirmModal.classList.add('hidden');

            if (resumeScanning && scanning) {
                scanPaused = false;
            }
        }

        function submitConfirmedScan() {
            const slotId = slotSelect.value;

            if (!slotId || !pendingToken) {
                closeConfirmModal(true);
                showBanner('error', 'No pending scan found. Please scan the QR code again.');
                return;
            }

            confirmLogBtn.disabled = true;
            confirmReturnBtn.disabled = true;

            fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ slot_id: slotId, qr_token: pendingToken }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    showBanner('success', '✓ Logged: ' + data.student_name + (data.student_id ? ' (' + data.student_id + ')' : ''));
                } else {
                    showBanner('error', '✗ ' + (data.message || data.errors?.qr_token?.[0] || 'Logging failed.'));
                }
            })
            .catch(() => showBanner('error', 'Network error. Please try again.'))
            .finally(() => {
                confirmLogBtn.disabled = false;
                confirmReturnBtn.disabled = false;
                closeConfirmModal(true);
            });
        }

        function showBanner(type, message) {
            resultBanner.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
            if (type === 'success') {
                resultBanner.classList.add('bg-green-100', 'text-green-800');
            } else {
                resultBanner.classList.add('bg-red-100', 'text-red-800');
            }
            resultBanner.textContent = message;
        }

        function clearBanner() {
            resultBanner.classList.add('hidden');
            resultBanner.textContent = '';
        }
    </script>
    @endpush
</x-app-layout>
