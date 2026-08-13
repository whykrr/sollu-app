export function formatDateID(dateInput) {
    if (!dateInput) return '';

    const bulanIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ]

    const tanggal = new Date(dateInput)
    const hari = tanggal.getDate()
    const bulan = bulanIndo[tanggal.getMonth()]
    const tahun = tanggal.getFullYear()

    return `${hari} ${bulan} ${tahun}`
}

export function formatDateTimeSimple(dateInput) {
    if (!dateInput) return '';

    const tanggal = new Date(dateInput)
    const tahun = tanggal.getFullYear()
    const bulan = String(tanggal.getMonth() + 1).padStart(2, '0')
    const hari = String(tanggal.getDate()).padStart(2, '0')
    const jam = String(tanggal.getHours()).padStart(2, '0')
    const menit = String(tanggal.getMinutes()).padStart(2, '0')

    return `${hari}/${bulan}/${tahun} ${jam}.${menit}`
}


export function formatDateTimeID(dateInput) {
    if (!dateInput) return '';

    const bulanIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ]

    const tanggal = new Date(dateInput)
    const hari = tanggal.getDate()
    const bulan = bulanIndo[tanggal.getMonth()]
    const tahun = tanggal.getFullYear()

    const jam = String(tanggal.getHours()).padStart(2, '0')
    const menit = String(tanggal.getMinutes()).padStart(2, '0')

    return `${hari} ${bulan} ${tahun} ${jam}:${menit}`
}

export function formatDateCompleteID(dateInput) {
    if (!dateInput) return '';

    const hariIndo = [
        'Minggu', 'Senin', 'Selasa', 'Rabu',
        'Kamis', 'Jumat', 'Sabtu',
    ]
    const bulanIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ]

    const tanggal = new Date(dateInput)
    const hari = tanggal.getDate()
    const bulan = bulanIndo[tanggal.getMonth()]
    const tahun = tanggal.getFullYear()
    const hariNama = hariIndo[tanggal.getDay()]

    return `${hariNama}, ${hari} ${bulan} ${tahun}`
}

export function gapDaysFromNow(expiredDate) {
    if (!expiredDate) return 0;

    const expired = new Date(expiredDate)
    const today = new Date()

    // Samakan ke tanggal saja (tanpa jam)
    const todayOnly = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate(),
    )
    const expiredOnly = new Date(
        expired.getFullYear(),
        expired.getMonth(),
        expired.getDate(),
    )

    const diffTime = expiredOnly - todayOnly
    const sisaHari = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    return sisaHari > 0 ? sisaHari : 0
};

// function addDays(date, days)
export function addDays(date, days) {
    if (!date) return null;

    const result = new Date(date)
    result.setDate(result.getDate() + days)
    return result
}

