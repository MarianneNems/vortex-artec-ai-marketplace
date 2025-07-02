from fastapi import APIRouter
from pydantic import BaseModel

router = APIRouter()


class ConnectWalletRequest(BaseModel):
    wallet_address: str
    chain_id: int


class ConnectWalletResponse(BaseModel):
    connected: bool
    wallet: str
    chain: str


@router.post("/connect", response_model=ConnectWalletResponse)
async def connect_wallet(request: ConnectWalletRequest):
    return {"connected": True, "wallet": request.wallet_address, "chain": "ethereum"}


@router.post("/mint")
async def mint_nft():
    return {
        "transaction_hash": "0x1234567890abcdef...",
        "token_id": "12345",
        "status": "pending",
        "estimated_confirmation": "2-5 minutes",
    }


@router.get("/transaction/{hash}")
async def get_transaction(hash: str):
    return {
        "hash": hash,
        "status": "confirmed",
        "block_number": 18500000,
        "gas_used": "150000",
        "timestamp": "2024-01-15T10:30:00Z",
    }
