// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";
import "@openzeppelin/contracts/utils/Counters.sol";

/**
 * @title TOLA-ART Daily Royalty Distribution Contract
 * @dev Automated royalty distribution for daily AI-generated artwork
 * 
 * Features:
 * - Enforced 5% royalty to creator (Marianne Nems)
 * - Remaining 95% distributed equally among participating artists
 * - Automatic TOLA token distribution upon sale
 * - Immutable royalty percentages
 * - Gas-optimized batch payments
 */
contract TOLAArtDailyRoyalty is ERC721, Ownable, ReentrancyGuard {
    using Counters for Counters.Counter;
    
    // === STATE VARIABLES ===
    
    Counters.Counter private _tokenIdCounter;
    
    // TOLA token contract
    IERC20 public immutable tolaToken;
    
    // Creator address (Marianne Nems) - immutable for security
    address public immutable creator;
    
    // Marketplace contract address
    address public marketplace;
    
    // VORTEX ARTEC admin address
    address public vortexAdmin;
    
    // Royalty percentages (immutable to prevent manipulation)
    uint256 public constant CREATOR_ROYALTY_PERCENTAGE = 5; // 5%
    uint256 public constant ARTIST_POOL_PERCENTAGE = 95; // 95%
    uint256 public constant PERCENTAGE_DENOMINATOR = 100;
    
    // Daily artwork tracking
    struct DailyArtwork {
        uint256 tokenId;
        string ipfsHash;
        string generationPrompt;
        uint256 generationDate;
        uint256 salePrice;
        bool royaltiesDistributed;
        address[] participatingArtists;
        mapping(address => bool) isParticipatingArtist;
        mapping(address => uint256) artistRoyaltyPaid;
    }
    
    // Artist participation tracking
    struct ArtistParticipation {
        address wallet;
        uint256 participationWeight;
        uint256 totalRoyaltiesEarned;
        uint256 participationCount;
        bool isActive;
    }
    
    // Royalty distribution tracking
    struct RoyaltyDistribution {
        uint256 dailyArtworkId;
        uint256 saleAmount;
        uint256 creatorRoyalty;
        uint256 artistPoolTotal;
        uint256 individualArtistShare;
        uint256 participatingArtistsCount;
        uint256 distributionTimestamp;
        bool isCompleted;
        string transactionHash;
    }
    
    // === MAPPINGS ===
    
    mapping(uint256 => DailyArtwork) public dailyArtworks;
    mapping(address => ArtistParticipation) public artistParticipations;
    mapping(uint256 => RoyaltyDistribution) public royaltyDistributions;
    mapping(string => uint256) public dateToArtworkId; // "YYYY-MM-DD" => tokenId
    
    // === EVENTS ===
    
    event DailyArtworkMinted(
        uint256 indexed tokenId,
        string date,
        string ipfsHash,
        uint256 participatingArtists
    );
    
    event ArtworkSold(
        uint256 indexed tokenId,
        address indexed buyer,
        uint256 salePrice,
        uint256 timestamp
    );
    
    event RoyaltyDistributed(
        uint256 indexed tokenId,
        uint256 distributionId,
        uint256 creatorRoyalty,
        uint256 artistPoolTotal,
        uint256 participatingArtistsCount
    );
    
    event CreatorRoyaltyPaid(
        uint256 indexed tokenId,
        address indexed creator,
        uint256 amount
    );
    
    event ArtistRoyaltyPaid(
        uint256 indexed tokenId,
        address indexed artist,
        uint256 amount,
        uint256 distributionId
    );
    
    event ArtistParticipationAdded(
        address indexed artist,
        uint256 indexed tokenId,
        uint256 participationWeight
    );
    
    event ArtistParticipationUpdated(
        address indexed artist,
        bool isActive,
        uint256 totalRoyalties
    );
    
    // === ERRORS ===
    
    error UnauthorizedAccess();
    error InvalidRoyaltyPercentage();
    error ArtworkAlreadyExists();
    error ArtworkNotFound();
    error RoyaltiesAlreadyDistributed();
    error InvalidParticipatingArtists();
    error InsufficientBalance();
    error TransferFailed();
    error InvalidSalePrice();
    error ArtistNotParticipating();
    
    // === MODIFIERS ===
    
    modifier onlyMarketplace() {
        if (msg.sender != marketplace) revert UnauthorizedAccess();
        _;
    }
    
    modifier onlyVortexAdmin() {
        if (msg.sender != vortexAdmin) revert UnauthorizedAccess();
        _;
    }
    
    modifier artworkExists(uint256 tokenId) {
        if (!_exists(tokenId)) revert ArtworkNotFound();
        _;
    }
    
    // === CONSTRUCTOR ===
    
    constructor(
        address _tolaToken,
        address _creator,
        address _marketplace,
        address _vortexAdmin
    ) ERC721("TOLA-ART Daily Collection", "TOLA-DAILY") {
        require(_tolaToken != address(0), "Invalid TOLA token address");
        require(_creator != address(0), "Invalid creator address");
        require(_marketplace != address(0), "Invalid marketplace address");
        require(_vortexAdmin != address(0), "Invalid admin address");
        
        tolaToken = IERC20(_tolaToken);
        creator = _creator;
        marketplace = _marketplace;
        vortexAdmin = _vortexAdmin;
    }
    
    // === MAIN FUNCTIONS ===
    
    /**
     * @dev Mint daily artwork NFT with participating artists
     * @param to Address to mint the NFT to (VORTEX ARTEC admin)
     * @param date Date string in format "YYYY-MM-DD"
     * @param ipfsHash IPFS hash of the artwork
     * @param generationPrompt The prompt used to generate the artwork
     * @param participatingArtists Array of artist wallet addresses
     * @param participationWeights Array of participation weights (must match artists length)
     */
    function mintDailyArtwork(
        address to,
        string memory date,
        string memory ipfsHash,
        string memory generationPrompt,
        address[] memory participatingArtists,
        uint256[] memory participationWeights
    ) external onlyVortexAdmin nonReentrant returns (uint256) {
        if (dateToArtworkId[date] != 0) revert ArtworkAlreadyExists();
        if (participatingArtists.length == 0) revert InvalidParticipatingArtists();
        if (participatingArtists.length != participationWeights.length) revert InvalidParticipatingArtists();
        
        _tokenIdCounter.increment();
        uint256 tokenId = _tokenIdCounter.current();
        
        // Mint NFT
        _safeMint(to, tokenId);
        
        // Initialize daily artwork
        DailyArtwork storage artwork = dailyArtworks[tokenId];
        artwork.tokenId = tokenId;
        artwork.ipfsHash = ipfsHash;
        artwork.generationPrompt = generationPrompt;
        artwork.generationDate = block.timestamp;
        artwork.royaltiesDistributed = false;
        
        // Add participating artists
        for (uint256 i = 0; i < participatingArtists.length; i++) {
            address artist = participatingArtists[i];
            uint256 weight = participationWeights[i];
            
            artwork.participatingArtists.push(artist);
            artwork.isParticipatingArtist[artist] = true;
            
            // Update artist participation
            ArtistParticipation storage participation = artistParticipations[artist];
            participation.wallet = artist;
            participation.participationWeight = weight;
            participation.participationCount++;
            participation.isActive = true;
            
            emit ArtistParticipationAdded(artist, tokenId, weight);
        }
        
        // Map date to token ID
        dateToArtworkId[date] = tokenId;
        
        emit DailyArtworkMinted(tokenId, date, ipfsHash, participatingArtists.length);
        
        return tokenId;
    }
    
    /**
     * @dev Process artwork sale and distribute royalties
     * @param tokenId The token ID of the sold artwork
     * @param buyer Address of the buyer
     * @param salePrice Sale price in TOLA tokens
     */
    function processArtworkSale(
        uint256 tokenId,
        address buyer,
        uint256 salePrice
    ) external onlyMarketplace nonReentrant artworkExists(tokenId) {
        if (salePrice == 0) revert InvalidSalePrice();
        
        DailyArtwork storage artwork = dailyArtworks[tokenId];
        
        if (artwork.royaltiesDistributed) revert RoyaltiesAlreadyDistributed();
        
        artwork.salePrice = salePrice;
        
        emit ArtworkSold(tokenId, buyer, salePrice, block.timestamp);
        
        // Distribute royalties immediately
        _distributeRoyalties(tokenId, salePrice);
    }
    
    /**
     * @dev Internal function to distribute royalties
     * @param tokenId The token ID
     * @param salePrice The sale price in TOLA tokens
     */
    function _distributeRoyalties(uint256 tokenId, uint256 salePrice) internal {
        DailyArtwork storage artwork = dailyArtworks[tokenId];
        
        // Calculate royalty amounts
        uint256 creatorRoyalty = (salePrice * CREATOR_ROYALTY_PERCENTAGE) / PERCENTAGE_DENOMINATOR;
        uint256 artistPoolTotal = (salePrice * ARTIST_POOL_PERCENTAGE) / PERCENTAGE_DENOMINATOR;
        uint256 participatingArtistsCount = artwork.participatingArtists.length;
        uint256 individualArtistShare = artistPoolTotal / participatingArtistsCount;
        
        // Create distribution record
        uint256 distributionId = _createDistributionRecord(
            tokenId,
            salePrice,
            creatorRoyalty,
            artistPoolTotal,
            individualArtistShare,
            participatingArtistsCount
        );
        
        // Transfer TOLA tokens from marketplace to this contract first
        require(
            tolaToken.transferFrom(marketplace, address(this), salePrice),
            "Failed to receive sale proceeds"
        );
        
        // Pay creator royalty
        _payCreatorRoyalty(tokenId, creatorRoyalty, distributionId);
        
        // Pay artist royalties
        _payArtistRoyalties(tokenId, individualArtistShare, distributionId);
        
        // Mark as distributed
        artwork.royaltiesDistributed = true;
        royaltyDistributions[distributionId].isCompleted = true;
        
        emit RoyaltyDistributed(
            tokenId,
            distributionId,
            creatorRoyalty,
            artistPoolTotal,
            participatingArtistsCount
        );
    }
    
    /**
     * @dev Create royalty distribution record
     */
    function _createDistributionRecord(
        uint256 tokenId,
        uint256 salePrice,
        uint256 creatorRoyalty,
        uint256 artistPoolTotal,
        uint256 individualArtistShare,
        uint256 participatingArtistsCount
    ) internal returns (uint256) {
        uint256 distributionId = uint256(keccak256(abi.encodePacked(
            tokenId,
            salePrice,
            block.timestamp,
            block.number
        )));
        
        RoyaltyDistribution storage distribution = royaltyDistributions[distributionId];
        distribution.dailyArtworkId = tokenId;
        distribution.saleAmount = salePrice;
        distribution.creatorRoyalty = creatorRoyalty;
        distribution.artistPoolTotal = artistPoolTotal;
        distribution.individualArtistShare = individualArtistShare;
        distribution.participatingArtistsCount = participatingArtistsCount;
        distribution.distributionTimestamp = block.timestamp;
        distribution.isCompleted = false;
        
        return distributionId;
    }
    
    /**
     * @dev Pay creator royalty
     */
    function _payCreatorRoyalty(uint256 tokenId, uint256 amount, uint256 distributionId) internal {
        require(tolaToken.transfer(creator, amount), "Creator royalty transfer failed");
        
        emit CreatorRoyaltyPaid(tokenId, creator, amount);
    }
    
    /**
     * @dev Pay artist royalties
     */
    function _payArtistRoyalties(uint256 tokenId, uint256 individualShare, uint256 distributionId) internal {
        DailyArtwork storage artwork = dailyArtworks[tokenId];
        
        for (uint256 i = 0; i < artwork.participatingArtists.length; i++) {
            address artist = artwork.participatingArtists[i];
            
            // Transfer TOLA to artist
            require(tolaToken.transfer(artist, individualShare), "Artist royalty transfer failed");
            
            // Update artist participation
            artistParticipations[artist].totalRoyaltiesEarned += individualShare;
            artwork.artistRoyaltyPaid[artist] = individualShare;
            
            emit ArtistRoyaltyPaid(tokenId, artist, individualShare, distributionId);
        }
    }
    
    // === VIEW FUNCTIONS ===
    
    /**
     * @dev Get daily artwork information
     */
    function getDailyArtwork(uint256 tokenId) external view returns (
        string memory ipfsHash,
        string memory generationPrompt,
        uint256 generationDate,
        uint256 salePrice,
        bool royaltiesDistributed,
        address[] memory participatingArtists
    ) {
        DailyArtwork storage artwork = dailyArtworks[tokenId];
        return (
            artwork.ipfsHash,
            artwork.generationPrompt,
            artwork.generationDate,
            artwork.salePrice,
            artwork.royaltiesDistributed,
            artwork.participatingArtists
        );
    }
    
    /**
     * @dev Get artist participation info
     */
    function getArtistParticipation(address artist) external view returns (
        uint256 participationWeight,
        uint256 totalRoyaltiesEarned,
        uint256 participationCount,
        bool isActive
    ) {
        ArtistParticipation storage participation = artistParticipations[artist];
        return (
            participation.participationWeight,
            participation.totalRoyaltiesEarned,
            participation.participationCount,
            participation.isActive
        );
    }
    
    /**
     * @dev Get royalty distribution details
     */
    function getRoyaltyDistribution(uint256 distributionId) external view returns (
        uint256 dailyArtworkId,
        uint256 saleAmount,
        uint256 creatorRoyalty,
        uint256 artistPoolTotal,
        uint256 individualArtistShare,
        uint256 participatingArtistsCount,
        uint256 distributionTimestamp,
        bool isCompleted
    ) {
        RoyaltyDistribution storage distribution = royaltyDistributions[distributionId];
        return (
            distribution.dailyArtworkId,
            distribution.saleAmount,
            distribution.creatorRoyalty,
            distribution.artistPoolTotal,
            distribution.individualArtistShare,
            distribution.participatingArtistsCount,
            distribution.distributionTimestamp,
            distribution.isCompleted
        );
    }
    
    /**
     * @dev Check if artist is participating in specific artwork
     */
    function isArtistParticipating(uint256 tokenId, address artist) external view returns (bool) {
        return dailyArtworks[tokenId].isParticipatingArtist[artist];
    }
    
    /**
     * @dev Get artwork by date
     */
    function getArtworkByDate(string memory date) external view returns (uint256) {
        return dateToArtworkId[date];
    }
    
    /**
     * @dev Get total participating artists for artwork
     */
    function getParticipatingArtistsCount(uint256 tokenId) external view returns (uint256) {
        return dailyArtworks[tokenId].participatingArtists.length;
    }
    
    /**
     * @dev Get current token ID counter
     */
    function getCurrentTokenId() external view returns (uint256) {
        return _tokenIdCounter.current();
    }
    
    // === ADMIN FUNCTIONS ===
    
    /**
     * @dev Update marketplace address (only owner)
     */
    function setMarketplace(address _marketplace) external onlyOwner {
        require(_marketplace != address(0), "Invalid marketplace address");
        marketplace = _marketplace;
    }
    
    /**
     * @dev Update VORTEX admin address (only owner)
     */
    function setVortexAdmin(address _vortexAdmin) external onlyOwner {
        require(_vortexAdmin != address(0), "Invalid admin address");
        vortexAdmin = _vortexAdmin;
    }
    
    /**
     * @dev Emergency function to recover stuck TOLA tokens (only owner)
     */
    function emergencyRecoverTola(uint256 amount) external onlyOwner {
        require(tolaToken.transfer(owner(), amount), "Recovery transfer failed");
    }
    
    /**
     * @dev Update artist participation status (only VORTEX admin)
     */
    function updateArtistParticipation(address artist, bool isActive) external onlyVortexAdmin {
        artistParticipations[artist].isActive = isActive;
        
        emit ArtistParticipationUpdated(
            artist,
            isActive,
            artistParticipations[artist].totalRoyaltiesEarned
        );
    }
    
    // === TOKEN URI ===
    
    /**
     * @dev Returns the token URI for metadata
     */
    function tokenURI(uint256 tokenId) public view override returns (string memory) {
        require(_exists(tokenId), "Token does not exist");
        
        DailyArtwork storage artwork = dailyArtworks[tokenId];
        
        return string(abi.encodePacked(
            "https://ipfs.io/ipfs/",
            artwork.ipfsHash
        ));
    }
    
    // === INTERFACE SUPPORT ===
    
    /**
     * @dev See {IERC165-supportsInterface}.
     */
    function supportsInterface(bytes4 interfaceId) public view virtual override returns (bool) {
        return super.supportsInterface(interfaceId);
    }
} 