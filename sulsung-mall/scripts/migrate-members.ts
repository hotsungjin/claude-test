/**
 * 고도몰 → Supabase 회원 마이그레이션 스크립트
 *
 * 실행: npx tsx scripts/migrate-members.ts
 * 테스트: npx tsx scripts/migrate-members.ts --dry-run
 */

import { createClient } from '@supabase/supabase-js'
import { parse } from 'csv-parse/sync'
import { readFileSync } from 'fs'
import { resolve } from 'path'
import { randomBytes } from 'crypto'

// ─── 설정 ──────────────────────────────────────────
const SUPABASE_URL = 'https://qeqwqsynbdgkwdkjxcdt.supabase.co'
const SUPABASE_SERVICE_KEY = process.env.SUPABASE_SERVICE_ROLE_KEY!
const CSV_PATH = resolve(__dirname, '../es_member.csv')
const BATCH_SIZE = 500
const DRY_RUN = process.argv.includes('--dry-run')

// ─── 등급 매핑 ────────────────────────────────────
const GRADE_MAP: Record<string, string> = {
  '일반회원': 'bronze',
  '오늘도설성': 'silver',
  '설성파트너스': 'vip',
}

// ─── 유틸 ─────────────────────────────────────────
function normalizePhone(phone: string | null | undefined): string {
  if (!phone) return ''
  return phone.replace(/[^0-9]/g, '')
}

function mapGender(g: string | null): string | null {
  if (g === 'm') return 'M'
  if (g === 'w') return 'F'
  return null
}

function generateReferralCode(): string {
  return randomBytes(4).toString('hex') // 8자리 고유 코드
}

// ─── 메인 ─────────────────────────────────────────
async function main() {
  if (!SUPABASE_SERVICE_KEY) {
    console.error('환경변수 SUPABASE_SERVICE_ROLE_KEY가 필요합니다.')
    console.error('실행: SUPABASE_SERVICE_ROLE_KEY=... npx tsx scripts/migrate-members.ts')
    process.exit(1)
  }

  const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_KEY)

  // 1. CSV 파싱
  console.log('📂 CSV 파일 읽는 중...')
  const raw = readFileSync(CSV_PATH, 'utf-8')
  const records: any[] = parse(raw, { columns: true, skip_empty_lines: true, relax_column_count: true })
  console.log(`  총 ${records.length}건 로드`)

  // 2. 기존 회원 전체 조회
  console.log('📋 기존 신몰 회원 조회 중...')
  let existing: any[] = []
  let offset = 0
  const PAGE = 1000
  while (true) {
    const { data, error } = await supabase
      .from('members')
      .select('id, phone, member_no, email')
      .range(offset, offset + PAGE - 1)
    if (error) { console.error('기존 회원 조회 실패:', error); process.exit(1) }
    if (!data || data.length === 0) break
    existing.push(...data)
    if (data.length < PAGE) break
    offset += PAGE
  }

  const phoneMap = new Map<string, { id: string; member_no: number; email: string }>()
  for (const m of existing) {
    const norm = normalizePhone(m.phone)
    if (norm) phoneMap.set(norm, { id: m.id, member_no: m.member_no, email: m.email })
  }
  console.log(`  기존 회원: ${phoneMap.size}명`)

  // 3. 분류
  const toUpdate: any[] = []
  const toInsert: any[] = []

  for (const r of records) {
    const phone = normalizePhone(r.phone)
    const match = phone ? phoneMap.get(phone) : null
    if (match) {
      toUpdate.push({ ...r, existingId: match.id })
    } else {
      toInsert.push(r)
    }
  }

  console.log(`\n📊 분류 결과:`)
  console.log(`  중복 (업데이트): ${toUpdate.length}명`)
  console.log(`  신규 (INSERT):   ${toInsert.length}명`)

  if (DRY_RUN) {
    console.log('\n🔍 DRY RUN 모드 - DB 변경 없이 종료')
    console.log('\n신규 첫 5건:')
    for (const r of toInsert.slice(0, 5)) {
      console.log(`  AUID=${r.auid}, ${r.name}, ${r.email}, ${normalizePhone(r.phone)}, ${r.grade_name}`)
    }
    console.log('\n중복 첫 5건:')
    for (const r of toUpdate.slice(0, 5)) {
      console.log(`  AUID=${r.auid}, ${r.name} → 기존ID: ${r.existingId}`)
    }
    return
  }

  // 4. 중복 회원 업데이트
  console.log('\n🔄 중복 회원 업데이트 중...')
  let updateCount = 0
  for (let i = 0; i < toUpdate.length; i += BATCH_SIZE) {
    const batch = toUpdate.slice(i, i + BATCH_SIZE)
    for (const r of batch) {
      const mileageGodo = Math.floor(parseFloat(r.mileage || '0'))
      const { error } = await supabase
        .from('members')
        .update({
          godomall_auid: parseInt(r.auid),
          ...(mileageGodo > 0 ? { mileage: mileageGodo } : {}),
        })
        .eq('id', r.existingId)

      if (error) console.error(`  실패 AUID=${r.auid}:`, error.message)
      else updateCount++
    }
    process.stdout.write(`  ${Math.min(i + BATCH_SIZE, toUpdate.length)}/${toUpdate.length}\r`)
  }
  console.log(`\n  ✅ ${updateCount}명 업데이트 완료`)

  // 5. 신규 회원 INSERT (배치)
  console.log('\n📥 신규 회원 INSERT 중...')
  let insertCount = 0
  let errorCount = 0
  const usedCodes = new Set<string>()

  for (let i = 0; i < toInsert.length; i += BATCH_SIZE) {
    const batch = toInsert.slice(i, i + BATCH_SIZE)
    const rows = batch.map(r => {
      let code = generateReferralCode()
      while (usedCodes.has(code)) code = generateReferralCode()
      usedCodes.add(code)

      return {
        name: (r.name || '').trim() || '미입력',
        email: r.email || null,
        phone: normalizePhone(r.phone) || '0000000000',
        birth_date: r.birth_date && r.birth_date !== '0000-00-00' ? r.birth_date : null,
        gender: mapGender(r.gender),
        grade: GRADE_MAP[r.grade_name] || 'bronze',
        mileage: Math.floor(parseFloat(r.mileage || '0')),
        godomall_auid: parseInt(r.auid),
        is_active: r.is_sleep !== 'y',
        created_at: r.created_at || new Date().toISOString(),
        referral_code: code,
      }
    })

    const { error } = await supabase.from('members').insert(rows)
    if (error) {
      // 배치 실패 시 개별 재시도
      for (const row of rows) {
        const { error: e } = await supabase.from('members').insert(row)
        if (e) {
          errorCount++
          if (errorCount <= 20) console.error(`  실패 AUID=${row.godomall_auid}: ${e.message}`)
        } else {
          insertCount++
        }
      }
    } else {
      insertCount += batch.length
    }

    if ((i / BATCH_SIZE) % 10 === 0 || i + BATCH_SIZE >= toInsert.length) {
      process.stdout.write(`  ${Math.min(i + BATCH_SIZE, toInsert.length)}/${toInsert.length} (성공: ${insertCount}, 실패: ${errorCount})\r`)
    }
  }
  console.log(`\n  ✅ ${insertCount}명 INSERT 완료 (실패: ${errorCount}건)`)

  // 6. member_no 시퀀스 재설정 안내
  console.log('\n🔢 member_no 시퀀스 재설정 필요:')
  console.log("  SELECT setval('members_member_no_seq', (SELECT MAX(member_no) FROM members));")

  // 7. 검증
  console.log('\n📊 검증 중...')
  const { count } = await supabase.from('members').select('*', { count: 'exact', head: true })
  console.log(`  최종 회원 수: ${count}명`)
  console.log('\n✅ 마이그레이션 완료!')
}

main().catch(console.error)
