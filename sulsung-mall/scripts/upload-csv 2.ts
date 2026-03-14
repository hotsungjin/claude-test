/**
 * CSV → _migration_members 임시 테이블 업로드
 * 실행: SUPABASE_SERVICE_ROLE_KEY=... npx tsx scripts/upload-csv.ts
 */

import { createClient } from '@supabase/supabase-js'
import { parse } from 'csv-parse/sync'
import { readFileSync } from 'fs'
import { resolve } from 'path'

const SUPABASE_URL = 'https://qeqwqsynbdgkwdkjxcdt.supabase.co'
const SUPABASE_SERVICE_KEY = process.env.SUPABASE_SERVICE_ROLE_KEY!
const CSV_PATH = resolve(__dirname, '../es_member.csv')
const BATCH_SIZE = 1000

async function main() {
  const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_KEY)

  console.log('📂 CSV 읽는 중...')
  const raw = readFileSync(CSV_PATH, 'utf-8')
  const records: any[] = parse(raw, { columns: true, skip_empty_lines: true, relax_column_count: true })
  console.log(`  ${records.length}건`)

  // 기존 데이터 삭제
  await supabase.from('_migration_members').delete().gte('auid', 0)

  console.log('📥 업로드 중...')
  let count = 0

  for (let i = 0; i < records.length; i += BATCH_SIZE) {
    const batch = records.slice(i, i + BATCH_SIZE).map(r => ({
      auid: parseInt(r.auid),
      mem_id: r.mem_id || null,
      name: r.name || null,
      email: r.email || null,
      phone: (r.phone || '').replace(/[^0-9]/g, '') || null,
      birth_date: r.birth_date && r.birth_date !== '0000-00-00' ? r.birth_date : null,
      gender: r.gender || null,
      grade_name: r.grade_name || null,
      mileage: parseFloat(r.mileage || '0'),
      deposit: parseFloat(r.deposit || '0'),
      created_at: r.created_at || null,
      last_login_at: r.last_login_at || null,
      sale_count: parseInt(r.sale_count || '0'),
      sale_amount: parseFloat(r.sale_amount || '0'),
      is_approved: r.is_approved || 'y',
      is_sleep: r.is_sleep || 'n',
      zipcode: r.zipcode || null,
      address: r.address || null,
      address_detail: r.address_detail || null,
      sms_agree: r.sms_agree || 'n',
      email_agree: r.email_agree || 'n',
    }))

    const { error } = await supabase.from('_migration_members').insert(batch)
    if (error) {
      console.error(`  배치 ${i} 실패:`, error.message)
      // 개별 재시도
      for (const row of batch) {
        const { error: e } = await supabase.from('_migration_members').upsert(row)
        if (e) console.error(`    AUID=${row.auid}: ${e.message}`)
        else count++
      }
    } else {
      count += batch.length
    }
    process.stdout.write(`  ${Math.min(i + BATCH_SIZE, records.length)}/${records.length}\r`)
  }

  console.log(`\n✅ ${count}건 업로드 완료`)
}

main().catch(console.error)
