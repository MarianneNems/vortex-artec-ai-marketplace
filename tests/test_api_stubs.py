import pytest
from fastapi.testclient import TestClient
from server.main import app

client = TestClient(app)


class TestAuthEndpoints:
    def test_login(self):
        response = client.post(
            "/auth/login", json={"username": "test_user", "password": "test_password"}
        )
        assert response.status_code == 200
        assert "token" in response.json()

    def test_refresh_token(self):
        response = client.post("/auth/refresh")
        assert response.status_code == 200

    def test_verify_token(self):
        response = client.get("/auth/verify")
        assert response.status_code == 200


class TestBlockchainEndpoints:
    def test_connect_wallet(self):
        response = client.post(
            "/blockchain/connect",
            json={"wallet_address": "0x1234567890abcdef", "chain_id": 1},
        )
        assert response.status_code == 200
        assert response.json()["connected"] is True

    def test_mint_nft(self):
        response = client.post("/blockchain/mint")
        assert response.status_code == 200

    def test_get_transaction(self):
        response = client.get("/blockchain/transaction/0x1234567890abcdef")
        assert response.status_code == 200


class TestMarketEndpoints:
    def test_get_market_trends(self):
        response = client.get("/market/trends")
        assert response.status_code == 200

    def test_get_market_trends_with_params(self):
        response = client.get("/market/trends?timeframe=7d&category=digital")
        assert response.status_code == 200

    def test_predict_market(self):
        response = client.get("/market/predict/nft_123")
        assert response.status_code == 200

    def test_get_market_opportunities(self):
        response = client.get("/market/opportunities")
        assert response.status_code == 200


class TestArtworkEndpoints:
    def test_get_artwork_analytics(self):
        response = client.get("/wp-json/vortex-ai/v1/artwork-analytics/123")
        assert response.status_code == 200

    def test_get_batch_analytics(self):
        response = client.post(
            "/wp-json/vortex-ai/v1/artwork-analytics/batch",
            json={"artwork_ids": [1, 2, 3]},
        )
        assert response.status_code == 200

    def test_get_category_analytics(self):
        response = client.get(
            "/wp-json/vortex-ai/v1/artwork-analytics/category/digital"
        )
        assert response.status_code == 200


class TestAIEndpoints:
    def test_get_recommendations(self):
        response = client.get("/api/v1/recommendations")
        assert response.status_code == 200

    def test_analyze_data(self):
        response = client.post(
            "/api/v1/analyze",
            json={"data": {"test": "data"}, "analysis_type": "market"},
        )
        assert response.status_code == 200


class TestMainEndpoints:
    def test_root(self):
        response = client.get("/")
        assert response.status_code == 200
        assert response.json()["status"] == "active"

    def test_health_check(self):
        response = client.get("/health")
        assert response.status_code == 200
        assert response.json()["status"] == "healthy"
