@extends('templates.layout')
@section('breadcrumbs', 'Rekom Break Event Point (BEP)')

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Rekom BEP</h5>
                <small class="text-muted">Simulasi</small>
            </div>
            <div class="card-body">

                {{-- Biaya Tetap (Fixed Cost) --}}
                <h6 class="fw-bold text-primary mb-2">Beban Tetap</h6>
                <table class="table table-bordered table-sm align-middle" id="table-fixed-cost">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nama Beban</th>
                            <th style="width:150px;">Total</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DEFAULT: 1 BARIS --}}
                        <tr>
                            <td>1</td>
                            <td>
                                <select class="form-control select-akun-beban">
                                    <option value="">-- Pilih Akun Beban --</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control input-total">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-danger btn-delete-row">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-sm btn-primary mb-4 mt-2 btn-add-row" data-target="#table-fixed-cost">
                    <i class="fas fa-plus"></i> Tambah Baris
                </button>

                {{-- Biaya Variabel --}}
                <h6 class="fw-bold text-primary mb-2">Beban Variabel</h6>
                <table class="table table-bordered table-sm align-middle" id="table-variable-cost">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nama Beban</th>
                            <th style="width:150px;">Total Biaya</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DEFAULT: 1 BARIS --}}
                        <tr>
                            <td>1</td>
                            <td>
                                <select class="form-control select-akun-beban">
                                    <option value="">-- Pilih Akun Beban --</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control input-total">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-danger btn-delete-row">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-sm btn-primary mb-4 mt-2 btn-add-row" data-target="#table-variable-cost">
                    <i class="fas fa-plus"></i> Tambah Baris
                </button>

                {{-- Penjualan --}}
                <h6 class="fw-bold text-primary mb-2">Penjualan</h6>
                <table class="table table-bordered table-sm align-middle" id="table-selling-price">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Jenis Penjualan</th>
                            <th style="width:150px;">Total Penjualan</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DEFAULT: 1 BARIS --}}
                        <tr>
                            <td>1</td>
                            <td>
                                {{-- select penjualan, opsi dari mst_akun id 15 & 17 --}}
                                <select class="form-control select-penjualan">
                                    <option value="">-- Pilih Akun Penjualan --</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control input-total">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-danger btn-delete-row">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-sm btn-primary mb-4 mt-2 btn-add-row" data-target="#table-selling-price">
                    <i class="fas fa-plus"></i> Tambah Baris
                </button>

                <div class="text-center">
                    <button class="btn btn-success" id="btn-hitung-bep">
                        <i class="fas fa-calculator"></i> Hitung BEP
                    </button>
                </div>
            </div>

            {{-- Hasil Simulasi --}}
            <div class="card-footer bg-info text-white">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">HASIL SIMULASI</h6>
                        <table class="table table-sm text-white mb-0">
                            <tr>
                                <td>Total Beban Tetap</td>
                                <td id="total-fixed-display">Rp. 0</td>
                            </tr>
                            <tr>
                                <td>Total Beban Variabel</td>
                                <td id="total-variable-display">Rp. 0</td>
                            </tr>
                            <tr>
                                <td>Total Penjualan</td>
                                <td id="total-sales-display">Rp. 0</td>
                            </tr>
                            <tr>
                                <td>Total Contribution Margin</td>
                                <td id="total-cm-display">Rp. 0</td>
                                {{-- Rumus : Total penjualan - total beban variabel --}}
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Break Even Point</h6>
                        <table class="table table-sm text-white mb-0">
                            <tr>
                                <td>Dalam Rupiah</td>
                                <td id="bep-rp-display">Rp. 0</td>
                                {{-- Total Beban Tetap / CM Ratio --}}
                            </tr>
                            <tr>
                                <td>CM Ratio</td>
                                <td id="cm-ratio-display">0 %</td>
                                {{-- Total CM / Total Penjualan * 100 --}}
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT TAMBAH / HAPUS BARIS + LOAD AKUN BEBAN & PENJUALAN + HITUNG BEP --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let akunBebanList = [];
            let penjualanList = [];

            // ===== Helper angka / rupiah =====
            function parseNumberFromInput(str) {
                if (!str) return 0;
                str = String(str).replace(/[^0-9,\.-]/g, '');
                str = str.replace(/\./g, '').replace(',', '.');
                const n = parseFloat(str);
                return isNaN(n) ? 0 : Math.abs(n);
            }


            function formatRupiah(value) {
                const n = Number(value) || 0;
                return n.toLocaleString('id-ID');
            }

            function sumInputs(tableSelector) {
                let total = 0;
                document.querySelectorAll(tableSelector + ' .input-total').forEach(input => {
                    total += parseNumberFromInput(input.value);
                });
                return total;
            }


            // ===== Load akun beban dari backend =====
            function loadAkunBeban() {
                fetch("{{ route('bep.akun-beban') }}")
                    .then(res => res.json())
                    .then(json => {
                        if (!json.ok) throw new Error('Gagal load akun beban');
                        akunBebanList = json.data || [];
                        refreshAllSelectAkunBeban();
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Gagal memuat akun beban. Coba reload halaman.');
                    });
            }

            function refreshAllSelectAkunBeban() {
                const selects = document.querySelectorAll('.select-akun-beban');
                selects.forEach(select => {
                    const current = select.value;
                    select.innerHTML = '';

                    const optEmpty = document.createElement('option');
                    optEmpty.value = '';
                    optEmpty.textContent = '-- Pilih Akun Beban --';
                    select.appendChild(optEmpty);

                    akunBebanList.forEach(a => {
                        const opt = document.createElement('option');
                        opt.value = a.id;
                        opt.textContent = (a.kode_akun ? a.kode_akun + ' - ' : '') + a.nama_akun;
                        const saldoPositif = Math.abs(Number(a.saldo_berjalan ?? 0));
                        opt.dataset.saldo = saldoPositif;

                        if (current && String(a.id) === String(current)) {
                            opt.selected = true;
                        }
                        select.appendChild(opt);
                    });
                });
            }

            // ===== Load akun penjualan (id 15 & 17) =====
            function loadPenjualan() {
                fetch("{{ route('bep.penjualan') }}")
                    .then(res => res.json())
                    .then(json => {
                        if (!json.ok) throw new Error('Gagal load penjualan');
                        penjualanList = json.data || [];
                        refreshAllSelectPenjualan();
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Gagal memuat akun penjualan. Coba reload halaman.');
                    });
            }

            function refreshAllSelectPenjualan() {
                const selects = document.querySelectorAll('.select-penjualan');
                selects.forEach(select => {
                    const current = select.value;
                    select.innerHTML = '';

                    const optEmpty = document.createElement('option');
                    optEmpty.value = '';
                    optEmpty.textContent = '-- Pilih Akun Penjualan --';
                    select.appendChild(optEmpty);

                    penjualanList.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = (p.kode_akun ? p.kode_akun + ' - ' : '') + p.nama_akun;
                        const saldoPositif = Math.abs(Number(p.saldo_berjalan ?? 0));
                        opt.dataset.saldo = saldoPositif;

                        if (current && String(p.id) === String(current)) {
                            opt.selected = true;
                        }
                        select.appendChild(opt);
                    });
                });
            }

            // ===== on change: beban & penjualan isi nilai total dari saldo_berjalan =====
            document.addEventListener('change', function(e) {
                // BEBAN
                const selectBeban = e.target.closest('.select-akun-beban');
                if (selectBeban) {
                    const opt = selectBeban.options[selectBeban.selectedIndex];
                    const saldo = Math.abs(Number(opt.dataset.saldo || 0));
                    const tr = selectBeban.closest('tr');
                    if (tr) {
                        const inputTotal = tr.querySelector('.input-total');
                        if (inputTotal) {
                            inputTotal.value = formatRupiah(saldo);
                        }
                    }
                    return;
                }

                // PENJUALAN
                const selectPenjualan = e.target.closest('.select-penjualan');
                if (selectPenjualan) {
                    const opt = selectPenjualan.options[selectPenjualan.selectedIndex];
                    const saldo = Math.abs(Number(opt.dataset.saldo || 0));
                    const tr = selectPenjualan.closest('tr');
                    if (tr) {
                        const inputTotal = tr.querySelector('.input-total');
                        if (inputTotal) {
                            inputTotal.value = formatRupiah(saldo);
                        }
                    }
                    return;
                }
            });

            // ===== Tambah baris =====
            document.querySelectorAll('.btn-add-row').forEach(button => {
                button.addEventListener('click', function() {
                    const tableSelector = this.dataset.target;
                    const tbody = document.querySelector(`${tableSelector} tbody`);
                    const firstRow = tbody.querySelector('tr');
                    if (!firstRow) return;

                    const newRow = firstRow.cloneNode(true);

                    newRow.querySelectorAll('input').forEach(input => input.value = '');
                    newRow.querySelectorAll('select').forEach(select => {
                        select.value = '';
                    });

                    tbody.appendChild(newRow);
                    updateRowNumbers(tbody);

                    // refresh opsi dropdown
                    refreshAllSelectAkunBeban();
                    refreshAllSelectPenjualan();
                });
            });

            // ===== Hapus baris (delegation) =====
            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-delete-row')) {
                    const row = e.target.closest('tr');
                    const tbody = row.parentElement;
                    if (tbody.querySelectorAll('tr').length > 1) { // jangan kosongin total
                        row.remove();
                        updateRowNumbers(tbody);
                    }
                }
            });

            function updateRowNumbers(tbody) {
                tbody.querySelectorAll('tr').forEach((tr, index) => {
                    tr.querySelector('td:first-child').textContent = index + 1;
                });
            }

            // ===== Hitung BEP =====
            const btnHitung = document.querySelector('#btn-hitung-bep');
            if (btnHitung) {
                btnHitung.addEventListener('click', function() {
                    const totalFixed = sumInputs('#table-fixed-cost');
                    const totalVariable = sumInputs('#table-variable-cost');
                    const totalSales = sumInputs('#table-selling-price');

                    const totalCM = totalSales - totalVariable;
                    const cmRatio = totalSales > 0 ? (totalCM / totalSales) * 100 : 0;
                    const bepRupiah = cmRatio > 0 ? (totalFixed / (cmRatio / 100)) : 0;

                    document.querySelector('#total-fixed-display').textContent =
                        'Rp. ' + formatRupiah(totalFixed);
                    document.querySelector('#total-variable-display').textContent =
                        'Rp. ' + formatRupiah(totalVariable);
                    document.querySelector('#total-sales-display').textContent =
                        'Rp. ' + formatRupiah(totalSales);
                    document.querySelector('#total-cm-display').textContent =
                        'Rp. ' + formatRupiah(totalCM);
                    document.querySelector('#cm-ratio-display').textContent =
                        cmRatio.toFixed(2) + ' %';
                    document.querySelector('#bep-rp-display').textContent =
                        'Rp. ' + formatRupiah(bepRupiah);
                });
            }

            // ===== Inisialisasi pertama =====
            loadAkunBeban();
            loadPenjualan();
        });
    </script>
@endsection
