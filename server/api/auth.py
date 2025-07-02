from fastapi import APIRouter
from pydantic import BaseModel

router = APIRouter()

class LoginRequest(BaseModel):
    username: str
    password: str

class LoginResponse(BaseModel):
    token: str
    refresh_token: str
    expires_in: int

@router.post("/login", response_model=LoginResponse)
async def login(request: LoginRequest):
    return {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600
    }

@router.post("/refresh")
async def refresh_token():
    return {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600
    }

@router.get("/verify")
async def verify_token():
    return {
        "valid": True,
        "user_id": "12345",
        "username": "example_user"
    } 