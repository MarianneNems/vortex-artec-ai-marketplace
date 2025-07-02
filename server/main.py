from fastapi import FastAPI
from server.api import auth, blockchain, market, artwork, ai

app = FastAPI(
    title="VORTEX AI AGENTS API",
    description="AI-powered marketplace API for artwork analytics and blockchain integration",
    version="1.0.0",
)

# Include all routers
app.include_router(auth.router, prefix="/auth", tags=["Authentication"])
app.include_router(blockchain.router, prefix="/blockchain", tags=["Blockchain"])
app.include_router(market.router, prefix="/market", tags=["Market"])
app.include_router(
    artwork.router, prefix="/wp-json/vortex-ai/v1", tags=["Artwork Analytics"]
)
app.include_router(ai.router, prefix="/api/v1", tags=["AI Services"])


@app.get("/")
async def root():
    return {"message": "VORTEX AI AGENTS API", "status": "active"}


@app.get("/health")
async def health_check():
    return {"status": "healthy", "version": "1.0.0"}
