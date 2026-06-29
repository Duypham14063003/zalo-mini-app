import { useEffect, useRef, useState } from "react";
import { closeApp, showToast } from "zmp-sdk";
import "@/css/lucky-wheel.scss";

type Stage = "form" | "loading" | "wheel";

type Prize = {
  label: string;
  subLabel: string;
  icon: string;
  tone: string;
};

const prizes: Prize[] = [
  {
    label: "5.000 VND",
    subLabel: "Vào ví TopUp",
    icon: "💸",
    tone: "#ffe2b0",
  },
  {
    label: "Túi canvas",
    subLabel: "Phiên bản giới hạn",
    icon: "👜",
    tone: "#ffd3c7",
  },
  {
    label: "Chai Ohar",
    subLabel: "Quà tặng mát lành",
    icon: "🥤",
    tone: "#cde8ff",
  },
  {
    label: "Gấu mini",
    subLabel: "Số lượng có hạn",
    icon: "🧸",
    tone: "#ffe7b5",
  },
  {
    label: "Mã freeship",
    subLabel: "Áp dụng toàn quốc",
    icon: "🚚",
    tone: "#d9f7d9",
  },
  {
    label: "TopUp 10K",
    subLabel: "Nạp ngay vào ví",
    icon: "🎁",
    tone: "#e3d6ff",
  },
];

const segmentAngle = 360 / prizes.length;

const districtOptions = [
  "Quận Bình Tân",
  "Quận 1",
  "Quận 3",
  "Quận 7",
  "Thủ Đức",
];

function getPrizePosition(index: number) {
  const angle =
    ((-90 + index * segmentAngle + segmentAngle / 2) * Math.PI) / 180;
  const radius = 34;

  return {
    left: `${50 + radius * Math.cos(angle)}%`,
    top: `${50 + radius * Math.sin(angle)}%`,
  };
}

function buildWheelBackground() {
  return `conic-gradient(
    #9fd8ff 0deg 60deg,
    #f8a04d 60deg 120deg,
    #9fd8ff 120deg 180deg,
    #f8a04d 180deg 240deg,
    #9fd8ff 240deg 300deg,
    #f8a04d 300deg 360deg
  )`;
}

function createInitialForm() {
  return {
    rewardCode: "LMAGMPGF",
    phone: "0901425782",
    fullName: "Duy Anh",
    district: districtOptions[0],
  };
}

export default function LuckyWheel() {
  const [stage, setStage] = useState<Stage>("form");
  const [winner, setWinner] = useState<Prize | null>(null);
  const [rotation, setRotation] = useState(0);
  const [isSpinning, setIsSpinning] = useState(false);
  const [pendingPrize, setPendingPrize] = useState<Prize | null>(null);
  const [loadingProgress, setLoadingProgress] = useState(12);
  const [formData, setFormData] = useState(createInitialForm);
  const loadingTimer = useRef<number | null>(null);
  const progressTimer = useRef<number | null>(null);

  useEffect(() => {
    return () => {
      if (loadingTimer.current) {
        window.clearTimeout(loadingTimer.current);
      }
      if (progressTimer.current) {
        window.clearInterval(progressTimer.current);
      }
    };
  }, []);

  const isFormValid = Object.values(formData).every(
    (value) => value.trim().length > 0,
  );

  const handleContinue = () => {
    if (!isFormValid) {
      return;
    }

    setStage("loading");
    setLoadingProgress(12);

    progressTimer.current = window.setInterval(() => {
      setLoadingProgress((current) => {
        if (current >= 92) {
          if (progressTimer.current) {
            window.clearInterval(progressTimer.current);
            progressTimer.current = null;
          }
          return current;
        }

        return current + 8;
      });
    }, 130);

    loadingTimer.current = window.setTimeout(() => {
      if (progressTimer.current) {
        window.clearInterval(progressTimer.current);
        progressTimer.current = null;
      }
      setLoadingProgress(100);
      setStage("wheel");
    }, 1800);
  };

  const handleSpin = () => {
    if (isSpinning) {
      return;
    }

    const selectedIndex = Math.floor(Math.random() * prizes.length);
    const selectedPrize = prizes[selectedIndex];
    const targetAngle = selectedIndex * segmentAngle + segmentAngle / 2;
    const pointerAngle = 270;
    const extraTurns = 360 * 6;
    const normalizedRotation = ((rotation % 360) + 360) % 360;
    const finalRotation =
      rotation +
      extraTurns +
      ((pointerAngle - targetAngle - normalizedRotation + 360) % 360);

    setPendingPrize(selectedPrize);
    setWinner(null);
    setIsSpinning(true);
    setRotation(finalRotation);
  };

  const handleClaimPrize = async () => {
    try {
      await showToast({ message: "Dang quay lai Zalo..." });
    } catch (error) {
      void error;
    }

    try {
      await closeApp();
    } catch (error) {
      window.history.back();
    }
  };

  return (
    <div className="campaign-shell">
      <div className="campaign-glow campaign-glow-left" />
      <div className="campaign-glow campaign-glow-right" />

      {stage === "form" && (
        <section className="campaign-page campaign-form-page">
          <div className="campaign-hero-card">
            <button type="button" className="campaign-rule-button">
              Thể lệ chương trình
            </button>
          </div>

          <div className="campaign-info-card">
            <div className="campaign-info-row">
              <span>So luot tham gia:</span>
              <strong>44</strong>
            </div>
            <div className="campaign-info-row">
              <span>So vi nuoc duoc ban:</span>
              <strong>4400</strong>
            </div>
            <div className="campaign-info-row">
              <span>Tổng số tiền:</span>
              <strong>44.000 VND</strong>
            </div>
          </div>

          <div className="campaign-form-card">
            <label className="campaign-field">
              <span>Mã dự thưởng*</span>
              <input
                value={formData.rewardCode}
                onChange={(event) =>
                  setFormData((current) => ({
                    ...current,
                    rewardCode: event.target.value,
                  }))
                }
                placeholder="Nhap ma du thuong"
              />
            </label>

            <label className="campaign-field">
              <span>Số điện thoại của bạn?*</span>
              <input
                value={formData.phone}
                onChange={(event) =>
                  setFormData((current) => ({
                    ...current,
                    phone: event.target.value,
                  }))
                }
                placeholder="Nhap so dien thoai"
              />
            </label>

            <label className="campaign-field">
              <span>Họ và tên của bạn?*</span>
              <input
                value={formData.fullName}
                onChange={(event) =>
                  setFormData((current) => ({
                    ...current,
                    fullName: event.target.value,
                  }))
                }
                placeholder="Nhap ho va ten"
              />
            </label>

            <label className="campaign-field">
              <span>Bạn ở quận/huyện nào?*</span>
              <select
                value={formData.district}
                onChange={(event) =>
                  setFormData((current) => ({
                    ...current,
                    district: event.target.value,
                  }))
                }
              >
                {districtOptions.map((district) => (
                  <option key={district} value={district}>
                    {district}
                  </option>
                ))}
              </select>
            </label>

            <button
              type="button"
              className="campaign-primary-button"
              disabled={!isFormValid}
              onClick={handleContinue}
            >
              Thêm lướt ngay
            </button>
          </div>
        </section>
      )}

      {stage === "loading" && (
        <section className="campaign-page campaign-loading-page">
          <div className="campaign-loading-copy">Dang tai...</div>
          <div className="campaign-loading-track">
            <div
              className="campaign-loading-fill"
              style={{ width: `${loadingProgress}%` }}
            />
          </div>
        </section>
      )}

      {stage === "wheel" && (
        <section className="campaign-page campaign-wheel-page">
          <div className="campaign-title-wrap">
            <div className="campaign-title-top">Vòng quay may mắn</div>
            <h1>Nổ tiền liền tay</h1>
          </div>

          <div className="campaign-wheel-scene">
            <div className="campaign-wheel-pointer" />
            <div className="campaign-wheel-ring">
              <div
                className="campaign-wheel"
                style={{
                  background: buildWheelBackground(),
                  transform: `rotate(${rotation}deg)`,
                  transition: isSpinning
                    ? "transform 5.4s cubic-bezier(0.18, 0.92, 0.15, 1)"
                    : undefined,
                }}
                onTransitionEnd={() => {
                  setIsSpinning(false);
                  setWinner(pendingPrize);
                }}
              >
                {prizes.map((prize, index) => (
                  <div
                    key={prize.label}
                    className="campaign-wheel-prize"
                    style={{
                      ...getPrizePosition(index),
                    }}
                  >
                    <div className="campaign-wheel-prize-text">
                      <strong>{prize.label}</strong>
                      <span>{prize.subLabel}</span>
                    </div>
                  </div>
                ))}

                <div className="campaign-wheel-center">
                  <div className="campaign-wheel-center-inner">
                    <span>19T</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button
            type="button"
            className="campaign-spin-button"
            onClick={handleSpin}
            disabled={isSpinning}
          >
            {isSpinning ? "Dang quay..." : "Quay ngay"}
          </button>

          {winner && (
            <div className="campaign-result-overlay">
              <div className="campaign-result-card">
                <div className="campaign-result-title">Chúc mừng!</div>
                <p>Chúc mừng bạn nhận được</p>
                <h2>{winner.label}</h2>
                <span>{winner.subLabel}</span>
                <button
                  type="button"
                  className="campaign-claim-button"
                  onClick={handleClaimPrize}
                >
                  Nhận thưởng ngay
                </button>
              </div>
            </div>
          )}
        </section>
      )}
    </div>
  );
}
