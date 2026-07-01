@php
    $playerName = $submission->player?->full_name ?? 'Không xác định';
    $playerPhone = $submission->player?->phone ?? 'Chưa có';
    $workspaceName = $submission->workspace?->name ?? 'Không xác định';
    $gameName = $submission->game?->name ?? 'Không xác định';
    $submittedAt = optional($submission->submitted_at)->format('d/m/Y H:i:s') ?? 'Chưa có';
    $latestWinningPrize = $winningRows->first();
@endphp

<div style="padding: 8px 6px 12px;">
    <div style="display: grid; gap: 18px;">
        <div style="border: 1px solid #f3dfb0; border-radius: 24px; background: linear-gradient(135deg, #fff9ec 0%, #ffffff 100%); box-shadow: 0 18px 40px rgba(217, 169, 54, 0.12); padding: 24px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; background: #fff1c9; color: #ae7414; padding: 8px 14px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;">
                        Lead vừa quay
                    </div>
                    <h3 style="margin: 16px 0 6px; font-size: 30px; line-height: 1.1; font-weight: 800; color: #1f2937;">
                        {{ $playerName }}
                    </h3>
                    <p style="margin: 0; font-size: 15px; color: #8a5b10;">
                        {{ $latestWinningPrize['label'] ?? $playerPhone }}
                    </p>
                </div>

                <div style="min-width: 220px; border-radius: 18px; background: #ffffff; border: 1px solid #fde7b2; padding: 16px 18px;">
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #b98a2f; margin-bottom: 10px;">
                        Phiên gửi
                    </div>
                    <div style="display: grid; gap: 8px;">
                        <div>
                            <div style="font-size: 12px; color: #9ca3af;">Lead ID</div>
                            <div style="font-size: 18px; font-weight: 800; color: #111827;">#{{ $submission->id }}</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #9ca3af;">Thời gian gửi</div>
                            <div style="font-size: 14px; font-weight: 700; color: #374151;">{{ $submittedAt }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px;">
            <div style="border: 1px solid #ececec; border-radius: 22px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 18px 20px; border-bottom: 1px solid #f3f4f6; font-size: 18px; font-weight: 800; color: #111827;">
                    Thông tin lead
                </div>

                <div style="padding: 8px 20px 20px;">
                    @foreach ([
                        'Workspace' => $workspaceName,
                        'Trò chơi' => $gameName,
                        'Người chơi' => $playerName,
                        'Số điện thoại' => $playerPhone,
                        'Nguồn' => $submission->source,
                        'Thời gian gửi' => $submittedAt,
                    ] as $label => $value)
                        <div style="display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 14px; padding: 14px 0; border-bottom: 1px solid #f3f4f6;">
                            <div style="font-size: 13px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em;">
                                {{ $label }}
                            </div>
                            <div style="font-size: 16px; font-weight: 700; color: #1f2937; word-break: break-word;">
                                {{ $value }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="border: 1px solid #ececec; border-radius: 22px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 18px 20px; border-bottom: 1px solid #f3f4f6; font-size: 18px; font-weight: 800; color: #111827;">
                    Dữ liệu form
                </div>

                <div style="padding: 18px 20px 20px; display: grid; gap: 12px;">
                    @forelse ($payloadRows as $row)
                        <div style="border: 1px solid #fde8b2; background: #fffaf0; border-radius: 18px; padding: 14px 16px;">
                            <div style="font-size: 12px; font-weight: 800; color: #b7791f; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                                {{ $row['label'] }}
                            </div>
                            <div style="font-size: 16px; line-height: 1.5; font-weight: 700; color: #1f2937; word-break: break-word;">
                                {{ $row['value'] }}
                            </div>
                        </div>
                    @empty
                        <div style="border: 1px dashed #d1d5db; background: #fafafa; border-radius: 18px; padding: 20px; font-size: 14px; color: #6b7280;">
                            Lead này chưa có dữ liệu form chi tiết.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div style="border: 1px solid #ececec; border-radius: 22px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 18px 20px; border-bottom: 1px solid #f3f4f6; font-size: 18px; font-weight: 800; color: #111827;">
                Quà đã nhận
            </div>

            <div style="padding: 18px 20px 20px; display: grid; gap: 12px;">
                @forelse ($winningRows as $row)
                    <div style="border: 1px solid #fde8b2; background: #fffaf0; border-radius: 18px; padding: 14px 16px;">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                            <div>
                                <div style="font-size: 12px; font-weight: 800; color: #b7791f; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                                    Phần thưởng
                                </div>
                                <div style="font-size: 18px; line-height: 1.4; font-weight: 800; color: #1f2937; word-break: break-word;">
                                    {{ $row['label'] }}
                                </div>
                            </div>
                            <div style="border-radius: 999px; background: #fff1c9; color: #ae7414; padding: 6px 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; white-space: nowrap;">
                                {{ $row['claim_status'] }}
                            </div>
                        </div>
                        <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; color: #4b5563;">
                            {{ $row['description'] }}
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #9ca3af;">
                            Thời gian trúng: {{ $row['resolved_at'] }}
                        </div>
                    </div>
                @empty
                    <div style="border: 1px dashed #d1d5db; background: #fafafa; border-radius: 18px; padding: 20px; font-size: 14px; color: #6b7280;">
                        Lead này chưa có phần thưởng nào được ghi nhận.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
