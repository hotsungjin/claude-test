from pydantic import BaseModel, EmailStr, field_validator


class SignupSchema(BaseModel):
    email: EmailStr
    username: str
    password: str
    password_confirm: str

    @field_validator("username")
    @classmethod
    def username_valid(cls, v: str) -> str:
        v = v.strip()
        if len(v) < 2 or len(v) > 20:
            raise ValueError("닉네임은 2~20자여야 합니다.")
        if not v.replace("_", "").isalnum():
            raise ValueError("닉네임은 영문, 숫자, 밑줄만 허용됩니다.")
        return v

    @field_validator("password")
    @classmethod
    def password_min_length(cls, v: str) -> str:
        if len(v) < 8:
            raise ValueError("비밀번호는 8자 이상이어야 합니다.")
        return v

    def validate_passwords_match(self) -> None:
        if self.password != self.password_confirm:
            raise ValueError("비밀번호가 일치하지 않습니다.")


class LoginSchema(BaseModel):
    email: EmailStr
    password: str
