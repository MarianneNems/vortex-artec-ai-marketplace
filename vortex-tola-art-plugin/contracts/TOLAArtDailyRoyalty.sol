// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/token/ERC721/extensions/ERC721URIStorage.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";
import "@openzeppelin/contracts/token/ERC20/IERC20.sol";

/**
 * @title TOLAArtDailyRoyalty
 * @dev Smart contract for TOLA-ART daily generation with dual royalty structure
 * 
 * ROYALTY STRUCTURE:
 * First Sale: 5% creator + 95% participating artists
 * Second+ Sales: 5% creator + 15% artists + 80% owner/reseller
 */
contract TOLAArtDailyRoyalty is ERC721, ERC721URIStorage, Ownable, ReentrancyGuard {
    
    // TOLA Token interface
    IERC20 public immutable tolaToken;
    
    // Creator wallet (Marianne Nems) - receives 5% on all sales
    address public immutable creator;
    
    // Marketplace contract address
    address public marketplace;
    
    // Royalty percentages (in basis points, 100 = 1%)
    uint256 public constant CREATOR_ROYALTY = 500; // 5%
    uint256 public constant FIRST_SALE_ARTIST_SHARE = 9500; // 95%
    uint256 public constant RESALE_ARTIST_SHARE = 1500; // 15%
    uint256 public constant RESALE_OWNER_SHARE = 8000; // 80%
    
    // Counter for token IDs
    uint256 private _tokenIdCounter;
    
    // Participating artists for each token
    mapping(uint256 => address[]) public participatingArtists;
    mapping(uint256 => mapping(address => bool)) public isParticipatingArtist;
    mapping(uint256 => uint256) public participatingArtistCount;
    
    // Track if token has been sold before (first sale vs resale)
    mapping(uint256 => bool) public hasBeenSold;
    mapping(uint256 => address) public originalOwner;
    
    // Sales tracking
    mapping(uint256 => uint256) public totalSales;
    mapping(uint256 => uint256) public creatorEarnings;
    mapping(uint256 => uint256) public artistEarnings;
    mapping(uint256 => uint256) public ownerEarnings;
    
    // Daily art metadata
    mapping(uint256 => string) public generationDate;
    mapping(uint256 => string) public artworkPrompt;
    mapping(uint256 => uint256) public dailyArtId;
    
    // Events
    event TokenMinted(uint256 indexed tokenId, string date, address[] artists);
    event FirstSaleCompleted(uint256 indexed tokenId, uint256 price, uint256 creatorAmount, uint256 artistAmount);
    event ResaleCompleted(uint256 indexed tokenId, uint256 price, uint256 creatorAmount, uint256 artistAmount, uint256 ownerAmount);
    event ArtistAdded(uint256 indexed tokenId, address artist);
    event RoyaltyDistributed(uint256 indexed tokenId, address recipient, uint256 amount, string royaltyType);
    
    constructor(
        address _tolaToken,
        address _creator,
        address _marketplace
    ) ERC721("TOLA-ART Daily Collection", "TOLA-ART") {
        tolaToken = IERC20(_tolaToken);
        creator = _creator;
        marketplace = _marketplace;
    }
    
    modifier onlyMarketplace() {
        require(msg.sender == marketplace, "Only marketplace can call this function");
        _;
    }
    
    /**
     * @dev Mint new TOLA-ART daily piece
     */
    function mintDailyArt(
        address to,
        string memory tokenURI,
        string memory date,
        string memory prompt,
        uint256 _dailyArtId,
        address[] memory artists
    ) public onlyOwner returns (uint256) {
        uint256 tokenId = _tokenIdCounter;
        _tokenIdCounter++;
        
        _safeMint(to, tokenId);
        _setTokenURI(tokenId, tokenURI);
        
        // Set metadata
        generationDate[tokenId] = date;
        artworkPrompt[tokenId] = prompt;
        dailyArtId[tokenId] = _dailyArtId;
        originalOwner[tokenId] = to;
        
        // Add participating artists
        for (uint256 i = 0; i < artists.length; i++) {
            if (!isParticipatingArtist[tokenId][artists[i]]) {
                participatingArtists[tokenId].push(artists[i]);
                isParticipatingArtist[tokenId][artists[i]] = true;
            }
        }
        participatingArtistCount[tokenId] = artists.length;
        
        emit TokenMinted(tokenId, date, artists);
        return tokenId;
    }
    
    /**
     * @dev Process sale with dual royalty structure
     */
    function processSale(
        uint256 tokenId,
        address seller,
        address buyer,
        uint256 price
    ) external onlyMarketplace nonReentrant {
        require(_exists(tokenId), "Token does not exist");
        require(price > 0, "Price must be greater than 0");
        
        bool isFirstSale = !hasBeenSold[tokenId];
        
        if (isFirstSale) {
            _processFirstSale(tokenId, seller, buyer, price);
        } else {
            _processResale(tokenId, seller, buyer, price);
        }
        
        // Mark as sold and transfer ownership
        hasBeenSold[tokenId] = true;
        totalSales[tokenId] += price;
        _transfer(seller, buyer, tokenId);
    }
    
    /**
     * @dev Process first sale: 5% creator + 95% artists
     */
    function _processFirstSale(
        uint256 tokenId,
        address seller,
        address buyer,
        uint256 price
    ) internal {
        uint256 creatorAmount = (price * CREATOR_ROYALTY) / 10000;
        uint256 artistPoolAmount = (price * FIRST_SALE_ARTIST_SHARE) / 10000;
        
        // Transfer creator royalty
        require(tolaToken.transferFrom(buyer, creator, creatorAmount), "Creator payment failed");
        creatorEarnings[tokenId] += creatorAmount;
        emit RoyaltyDistributed(tokenId, creator, creatorAmount, "creator_first_sale");
        
        // Distribute to participating artists
        uint256 artistCount = participatingArtistCount[tokenId];
        if (artistCount > 0) {
            uint256 perArtistAmount = artistPoolAmount / artistCount;
            uint256 remainingAmount = artistPoolAmount;
            
            for (uint256 i = 0; i < artistCount; i++) {
                address artist = participatingArtists[tokenId][i];
                uint256 artistAmount = (i == artistCount - 1) ? remainingAmount : perArtistAmount;
                
                require(tolaToken.transferFrom(buyer, artist, artistAmount), "Artist payment failed");
                artistEarnings[tokenId] += artistAmount;
                remainingAmount -= artistAmount;
                
                emit RoyaltyDistributed(tokenId, artist, artistAmount, "artist_first_sale");
            }
        }
        
        emit FirstSaleCompleted(tokenId, price, creatorAmount, artistPoolAmount);
    }
    
    /**
     * @dev Process resale: 5% creator + 15% artists + 80% owner/reseller
     */
    function _processResale(
        uint256 tokenId,
        address seller,
        address buyer,
        uint256 price
    ) internal {
        uint256 creatorAmount = (price * CREATOR_ROYALTY) / 10000;
        uint256 artistPoolAmount = (price * RESALE_ARTIST_SHARE) / 10000;
        uint256 ownerAmount = (price * RESALE_OWNER_SHARE) / 10000;
        
        // Transfer creator royalty
        require(tolaToken.transferFrom(buyer, creator, creatorAmount), "Creator payment failed");
        creatorEarnings[tokenId] += creatorAmount;
        emit RoyaltyDistributed(tokenId, creator, creatorAmount, "creator_resale");
        
        // Distribute to participating artists (15%)
        uint256 artistCount = participatingArtistCount[tokenId];
        if (artistCount > 0) {
            uint256 perArtistAmount = artistPoolAmount / artistCount;
            uint256 remainingArtistAmount = artistPoolAmount;
            
            for (uint256 i = 0; i < artistCount; i++) {
                address artist = participatingArtists[tokenId][i];
                uint256 artistAmount = (i == artistCount - 1) ? remainingArtistAmount : perArtistAmount;
                
                require(tolaToken.transferFrom(buyer, artist, artistAmount), "Artist payment failed");
                artistEarnings[tokenId] += artistAmount;
                remainingArtistAmount -= artistAmount;
                
                emit RoyaltyDistributed(tokenId, artist, artistAmount, "artist_resale");
            }
        }
        
        // Transfer to current owner/reseller (80%)
        require(tolaToken.transferFrom(buyer, seller, ownerAmount), "Owner payment failed");
        ownerEarnings[tokenId] += ownerAmount;
        emit RoyaltyDistributed(tokenId, seller, ownerAmount, "owner_resale");
        
        emit ResaleCompleted(tokenId, price, creatorAmount, artistPoolAmount, ownerAmount);
    }
    
    /**
     * @dev Add artist to existing token (only before first sale)
     */
    function addParticipatingArtist(uint256 tokenId, address artist) external onlyOwner {
        require(_exists(tokenId), "Token does not exist");
        require(!hasBeenSold[tokenId], "Cannot add artists after first sale");
        require(!isParticipatingArtist[tokenId][artist], "Artist already participating");
        
        participatingArtists[tokenId].push(artist);
        isParticipatingArtist[tokenId][artist] = true;
        participatingArtistCount[tokenId]++;
        
        emit ArtistAdded(tokenId, artist);
    }
    
    /**
     * @dev Update marketplace address
     */
    function updateMarketplace(address _marketplace) external onlyOwner {
        marketplace = _marketplace;
    }
    
    /**
     * @dev Get participating artists for a token
     */
    function getParticipatingArtists(uint256 tokenId) external view returns (address[] memory) {
        return participatingArtists[tokenId];
    }
    
    /**
     * @dev Get royalty breakdown for a potential sale
     */
    function getRoyaltyBreakdown(uint256 tokenId, uint256 price) external view returns (
        uint256 creatorAmount,
        uint256 artistAmount,
        uint256 ownerAmount,
        bool isFirstSale
    ) {
        require(_exists(tokenId), "Token does not exist");
        
        isFirstSale = !hasBeenSold[tokenId];
        creatorAmount = (price * CREATOR_ROYALTY) / 10000;
        
        if (isFirstSale) {
            artistAmount = (price * FIRST_SALE_ARTIST_SHARE) / 10000;
            ownerAmount = 0;
        } else {
            artistAmount = (price * RESALE_ARTIST_SHARE) / 10000;
            ownerAmount = (price * RESALE_OWNER_SHARE) / 10000;
        }
    }
    
    /**
     * @dev Get token information
     */
    function getTokenInfo(uint256 tokenId) external view returns (
        string memory date,
        string memory prompt,
        uint256 _dailyArtId,
        address[] memory artists,
        bool sold,
        uint256 sales,
        address originalOwnerAddress
    ) {
        require(_exists(tokenId), "Token does not exist");
        
        return (
            generationDate[tokenId],
            artworkPrompt[tokenId],
            dailyArtId[tokenId],
            participatingArtists[tokenId],
            hasBeenSold[tokenId],
            totalSales[tokenId],
            originalOwner[tokenId]
        );
    }
    
    // Override functions
    function _burn(uint256 tokenId) internal override(ERC721, ERC721URIStorage) {
        super._burn(tokenId);
    }
    
    function tokenURI(uint256 tokenId) public view override(ERC721, ERC721URIStorage) returns (string memory) {
        return super.tokenURI(tokenId);
    }
    
    function supportsInterface(bytes4 interfaceId) public view override(ERC721, ERC721URIStorage) returns (bool) {
        return super.supportsInterface(interfaceId);
    }
} 