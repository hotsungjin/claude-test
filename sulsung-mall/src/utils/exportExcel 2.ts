import * as XLSX from 'xlsx'

export function downloadExcel(
  headers: string[],
  rows: (string | number)[][],
  filename: string,
) {
  const ws = XLSX.utils.aoa_to_sheet([headers, ...rows])

  // 컬럼 너비 자동 조정
  ws['!cols'] = headers.map((h, col) => {
    let max = h.length
    for (const row of rows) {
      const len = String(row[col] ?? '').length
      if (len > max) max = len
    }
    return { wch: Math.min(max + 4, 50) }
  })

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Sheet1')
  XLSX.writeFile(wb, `${filename}.xlsx`)
}
