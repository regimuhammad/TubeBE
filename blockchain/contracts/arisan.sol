// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract Arisan {
    address public admin;
    address[] public participants;

    mapping(address => bool) public hasPaid;
    mapping(address => bool) public hasWon;

    address public lastWinner;
    address public currentDrawer;

    uint public round = 1;
    uint public fee;                 // biaya per peserta
    uint public lastDrawTime;       // waktu draw terakhir
    uint public drawInterval;       // jarak waktu antar draw (detik)

    constructor(uint _feeInWei, uint _drawIntervalInSeconds) {
        admin = msg.sender;
        fee = _feeInWei;
        drawInterval = _drawIntervalInSeconds;
        lastDrawTime = block.timestamp;
    }

    function pay() public payable {
        require(msg.value == fee, "Nominal salah");
        if (!isParticipant(msg.sender)) {
            participants.push(msg.sender);
        }
        hasPaid[msg.sender] = true;

        // Set currentDrawer pertama kali
        if (currentDrawer == address(0)) {
            currentDrawer = participants[0];
        }
    }

    function drawWinner() public {
        require(msg.sender == currentDrawer, "Bukan giliran kamu untuk draw");
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

        // tentukan currentDrawer berikutnya
        currentDrawer = getNextEligibleDrawer();

        round++;
        lastDrawTime = block.timestamp;
    }

    function getNextEligibleDrawer() internal view returns (address) {
        if (participants.length == 0) return address(0);

        uint currentIndex = 0;
        for (uint i = 0; i < participants.length; i++) {
            if (participants[i] == currentDrawer) {
                currentIndex = i;
                break;
            }
        }

        // cari berikutnya yang belum menang
        for (uint offset = 1; offset <= participants.length; offset++) {
            uint nextIndex = (currentIndex + offset) % participants.length;
            if (!hasWon[participants[nextIndex]]) {
                return participants[nextIndex];
            }
        }

        // kalau semua sudah menang, return admin saja
        return admin;
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
