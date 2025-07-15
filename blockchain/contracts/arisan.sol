// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract Arisan {
    address public admin;
    address[] public participants;

    mapping(address => bool) public hasPaid;
    mapping(address => bool) public hasWon;

    address public lastWinner;

    uint public round = 1;
    uint public fee;                 // biaya per peserta
    uint public lastDrawTime;       // waktu draw terakhir
    uint public drawInterval;       // jarak waktu antar draw (detik)

    constructor(uint _feeInWei, uint _drawIntervalInSeconds) {
        admin = msg.sender;
        fee = _feeInWei;
        drawInterval = _drawIntervalInSeconds;
        lastDrawTime = block.timestamp; // mulai dihitung dari deploy
    }

    modifier onlyParticipant() {
        require(isParticipant(msg.sender), "Kamu belum join arisan");
        _;
    }

    function join() public {
        require(!isParticipant(msg.sender), "Kamu Sudah Join");
        participants.push(msg.sender);
    }

    function pay() public payable onlyParticipant {
        require(msg.value == fee, "Nominal salah");
        hasPaid[msg.sender] = true;
    }

    function drawWinner() public onlyParticipant {
        require(allPaid(), "Belum semua bayar");
        require(block.timestamp >= lastDrawTime + drawInterval, "Belum waktunya draw");

        uint eligibleCount = 0;
        for (uint i = 0; i < participants.length; i++) {
            if (!hasWon[participants[i]]) {
                eligibleCount++;
            }
        }
        require(eligibleCount > 0, "Arisan selesai, semua sudah menang");

        uint winnerIndex;
        address winner;

        // pilih secara acak yang belum pernah menang
        do {
            winnerIndex = uint(
                keccak256(abi.encodePacked(block.timestamp, block.prevrandao, round))
            ) % participants.length;

            winner = participants[winnerIndex];
        } while (hasWon[winner]);

        lastWinner = winner;
        hasWon[winner] = true;

        payable(lastWinner).transfer(address(this).balance);

        // reset pembayaran untuk ronde berikutnya
        for (uint i = 0; i < participants.length; i++) {
            hasPaid[participants[i]] = false;
        }

        round++;
        lastDrawTime = block.timestamp;
    }

    function isParticipant(address user) public view returns (bool) {
        for (uint i = 0; i < participants.length; i++) {
            if (participants[i] == user) return true;
        }
        return false;
    }

    function allPaid() internal view returns (bool) {
        for (uint i = 0; i < participants.length; i++) {
            if (!hasPaid[participants[i]]) return false;
        }
        return true;
    }

    function getParticipants() public view returns (address[] memory) {
        return participants;
    }

    function getRemainingTime() public view returns (uint) {
        if (block.timestamp >= lastDrawTime + drawInterval) {
            return 0;
        }
        return (lastDrawTime + drawInterval) - block.timestamp;
    }

    function getEligibleParticipants() public view returns (address[] memory) {
        uint count = 0;
        for (uint i = 0; i < participants.length; i++) {
            if (!hasWon[participants[i]]) {
                count++;
            }
        }

        address[] memory eligible = new address[](count);
        uint j = 0;
        for (uint i = 0; i < participants.length; i++) {
            if (!hasWon[participants[i]]) {
                eligible[j] = participants[i];
                j++;
            }
        }
        return eligible;
    }

    receive() external payable {
        revert("Gunakan fungsi pay()");
    }
}
