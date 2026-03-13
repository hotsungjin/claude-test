// Supabase DB 타입 정의
// npx supabase gen types typescript --project-id YOUR_PROJECT_ID 로 자동 생성 가능

export type Json = string | number | boolean | null | { [key: string]: Json } | Json[]

export type MemberGrade = '일반' | '우수' | 'VIP' | 'VVIP'
export type GoodsStatus = 'active' | 'inactive' | 'soldout' | 'deleted'
export type OrderStatus =
  | 'pending_payment' | 'paid' | 'preparing' | 'shipped'
  | 'delivered' | 'confirmed' | 'cancel_requested' | 'cancelled'
  | 'return_requested' | 'returning' | 'returned'
  | 'exchange_requested' | 'exchanged'
export type PaymentStatus = 'pending' | 'done' | 'cancelled' | 'partial_cancelled' | 'failed'
export type NotificationChannel = 'kakao' | 'sms' | 'email'
export type AdminRole = 'superadmin' | 'admin' | 'staff'

export interface Database {
  public: {
    Tables: {
      members: {
        Row: {
          id: string
          auth_id: string | null
          email: string | null
          name: string
          phone: string
          birth_date: string | null
          gender: 'M' | 'F' | 'N' | null
          grade: MemberGrade
          mileage: number
          mileage_expires_at: string | null
          deposit: number
          social_provider: string | null
          social_id: string | null
          is_active: boolean
          is_dormant: boolean
          dormant_at: string | null
          marketing_sms: boolean
          marketing_email: boolean
          marketing_kakao: boolean
          last_login_at: string | null
          created_at: string
          updated_at: string
        }
        Insert: Omit<Database['public']['Tables']['members']['Row'], 'id' | 'created_at' | 'updated_at'>
        Update: Partial<Database['public']['Tables']['members']['Insert']>
      }
      goods: {
        Row: {
          id: string
          category_id: number | null
          name: string
          slug: string
          summary: string | null
          description: string | null
          origin: string | null
          manufacturer: string | null
          brand: string | null
          brand_id: number | null
          weight: number | null
          price: number
          sale_price: number | null
          cost_price: number | null
          tax_type: 'taxable' | 'free' | 'zero'
          status: GoodsStatus
          stock: number
          stock_alert_qty: number
          is_option: boolean
          is_bundle: boolean
          is_gift: boolean
          mileage_rate: number
          thumbnail_url: string | null
          images: Json
          required_info: Json
          tags: string[]
          naver_category: string | null
          google_category: string | null
          view_count: number
          sale_count: number
          sort_order: number
          created_at: string
          updated_at: string
        }
        Insert: Omit<Database['public']['Tables']['goods']['Row'], 'id' | 'created_at' | 'updated_at' | 'view_count' | 'sale_count'>
        Update: Partial<Database['public']['Tables']['goods']['Insert']>
      }
      orders: {
        Row: {
          id: string
          order_no: string
          member_id: string | null
          guest_name: string | null
          guest_phone: string | null
          guest_email: string | null
          goods_amount: number
          discount_amount: number
          coupon_amount: number
          mileage_used: number
          deposit_used: number
          shipping_amount: number
          total_amount: number
          recipient: string
          phone: string
          zipcode: string
          address1: string
          address2: string | null
          delivery_memo: string | null
          status: OrderStatus
          admin_memo: string | null
          user_memo: string | null
          paid_at: string | null
          confirmed_at: string | null
          created_at: string
          updated_at: string
        }
        Insert: Omit<Database['public']['Tables']['orders']['Row'], 'id' | 'created_at' | 'updated_at'>
        Update: Partial<Database['public']['Tables']['orders']['Insert']>
      }
      categories: {
        Row: {
          id: number
          parent_id: number | null
          name: string
          slug: string
          sort_order: number
          is_active: boolean
          image_url: string | null
          created_at: string
        }
        Insert: Omit<Database['public']['Tables']['categories']['Row'], 'id' | 'created_at'>
        Update: Partial<Database['public']['Tables']['categories']['Insert']>
      }
      brands: {
        Row: {
          id: number
          name: string
          slug: string
          logo_url: string | null
          description: string | null
          is_active: boolean
          sort_order: number
          created_at: string
        }
        Insert: Omit<Database['public']['Tables']['brands']['Row'], 'id' | 'created_at'>
        Update: Partial<Database['public']['Tables']['brands']['Insert']>
      }
    }
  }
}
