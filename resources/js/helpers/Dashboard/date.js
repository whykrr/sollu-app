export function formatDateID(dateInput) {
    const bulanIndo = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    const tanggal = new Date(dateInput);
    const hari = tanggal.getDate();
    const bulan = bulanIndo[tanggal.getMonth()];
    const tahun = tanggal.getFullYear();

    return `${hari} ${bulan} ${tahun}`;
}

export function formatDateCompleteID(dateInput) {
    const hariIndo = [
        "Minggu", "Senin", "Selasa", "Rabu",
        "Kamis", "Jumat", "Sabtu"
    ];
    const bulanIndo = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    const tanggal = new Date(dateInput);
    const hari = tanggal.getDate();
    const bulan = bulanIndo[tanggal.getMonth()];
    const tahun = tanggal.getFullYear();
    const hariNama = hariIndo[tanggal.getDay()];

    return `${hariNama}, ${hari} ${bulan} ${tahun}`;
}

export function gapDaysFromNow(expiredDate) {
    const expired = new Date(expiredDate);
    const today = new Date();

    // Samakan ke tanggal saja (tanpa jam)
    const todayOnly = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate()
    );
    const expiredOnly = new Date(
        expired.getFullYear(),
        expired.getMonth(),
        expired.getDate()
    );

    const diffTime = expiredOnly - todayOnly;
    const sisaHari = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return sisaHari > 0 ? sisaHari : 0;
};

// function addDays(date, days)
export function addDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

