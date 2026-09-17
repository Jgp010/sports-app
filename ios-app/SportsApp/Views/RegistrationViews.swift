import SwiftUI

struct RegistrationFormView: View {
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    let event: SportsEvent
    @State private var selectedItems: Set<Int> = []
    @State private var phone = ""
    @State private var organization = ""
    @State private var emergencyName = ""
    @State private var emergencyPhone = ""
    @State private var notes = ""
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var completedRegistration: Registration?

    private var total: Int {
        event.items.filter { selectedItems.contains($0.id) }.reduce(0) { $0 + $1.currentFee }
    }

    var body: some View {
        Form {
            Section("選擇賽事項目") {
                ForEach(event.items) { item in
                    Button {
                        if selectedItems.contains(item.id) { selectedItems.remove(item.id) }
                        else { selectedItems.insert(item.id) }
                    } label: {
                        HStack {
                            Image(systemName: selectedItems.contains(item.id) ? "checkmark.circle.fill" : "circle")
                                .foregroundStyle(selectedItems.contains(item.id) ? .indigo : .secondary)
                            Text(item.name).foregroundStyle(.primary)
                            Spacer()
                            Text(AppFormatters.money(item.currentFee)).foregroundStyle(.secondary)
                        }
                    }
                }
                HStack { Text("合計").bold(); Spacer(); Text(AppFormatters.money(total)).bold().foregroundStyle(.indigo) }
            }
            Section("報名資料") {
                TextField("單位（必填）", text: $organization)
                TextField("聯絡電話（必填）", text: $phone).keyboardType(.phonePad)
                TextField("緊急聯絡人（必填）", text: $emergencyName)
                TextField("緊急聯絡電話（必填）", text: $emergencyPhone).keyboardType(.phonePad)
                TextField("備註（選填）", text: $notes, axis: .vertical).lineLimit(3...6)
            }
            if let errorMessage { Section { Text(errorMessage).foregroundStyle(.red).font(.footnote) } }
            Section {
                Button("確認送出報名") { Task { await submit() } }
                    .frame(maxWidth: .infinity)
                    .disabled(!isValid || isLoading)
            }
        }
        .navigationTitle(event.title)
        .navigationBarTitleDisplayMode(.inline)
        .toolbar { ToolbarItem(placement: .cancellationAction) { Button("關閉") { dismiss() } } }
        .loading(isLoading)
        .alert("報名成功", isPresented: Binding(
            get: { completedRegistration != nil },
            set: { if !$0 { completedRegistration = nil } }
        )) {
            Button("完成") { dismiss() }
        } message: {
            Text("報名編號：\(completedRegistration?.registrationNo ?? "")")
        }
        .onAppear { phone = session.member?.phone ?? "" }
    }

    private var isValid: Bool {
        !selectedItems.isEmpty && organization.nilIfBlank != nil && phone.nilIfBlank != nil && emergencyName.nilIfBlank != nil && emergencyPhone.nilIfBlank != nil
    }

    private func submit() async {
        guard let token = session.token else { return }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            completedRegistration = try await APIClient.shared.registerEvent(
                token: token,
                slug: event.slug,
                body: RegistrationRequest(
                    contactPhone: phone, organization: organization,
                    emergencyContactName: emergencyName, emergencyContactPhone: emergencyPhone,
                    itemIds: selectedItems.sorted(), notes: notes.nilIfBlank
                )
            )
        } catch {
            session.handleUnauthorized(error)
            errorMessage = error.localizedDescription
        }
    }
}

struct RegistrationListView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var registrations: [Registration] = []
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        Group {
            if let errorMessage, registrations.isEmpty {
                ErrorStateView(message: errorMessage) { Task { await load() } }
            } else if registrations.isEmpty && !isLoading {
                EmptyStateView(title: "尚無報名紀錄", icon: "list.clipboard")
            } else {
                List(registrations) { registration in
                    NavigationLink(value: registration) {
                        VStack(alignment: .leading, spacing: 6) {
                            HStack {
                                Text(registration.event.title).font(.headline)
                                Spacer()
                                Text(registration.statusText).font(.caption.bold())
                                    .foregroundStyle(registration.status == "registered" ? .green : .secondary)
                            }
                            Text(registration.registrationNo).font(.caption.monospaced()).foregroundStyle(.secondary)
                            Text(registration.items.map(\.name).joined(separator: "、")).font(.subheadline)
                            Text(AppFormatters.dateTime(registration.registeredAt)).font(.caption).foregroundStyle(.tertiary)
                        }
                        .padding(.vertical, 4)
                    }
                }
                .refreshable { await load() }
                .navigationDestination(for: Registration.self) { registration in
                    RegistrationDetailView(registration: registration) { await load() }
                }
            }
        }
        .navigationTitle("我的報名紀錄")
        .loading(isLoading && registrations.isEmpty)
        .task { await load() }
    }

    private func load() async {
        guard let token = session.token else { return }
        isLoading = true
        defer { isLoading = false }
        do {
            registrations = try await APIClient.shared.registrations(token: token)
            errorMessage = nil
        } catch {
            session.handleUnauthorized(error)
            errorMessage = error.localizedDescription
        }
    }
}

struct RegistrationDetailView: View {
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    let registration: Registration
    let onChanged: () async -> Void
    @State private var showCancelConfirmation = false
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        List {
            Section("報名資訊") {
                LabeledContent("報名編號", value: registration.registrationNo)
                LabeledContent("狀態", value: registration.statusText)
                LabeledContent("賽事", value: registration.event.title)
                LabeledContent("單位", value: registration.organization ?? "—")
                LabeledContent("報名時間", value: AppFormatters.dateTime(registration.registeredAt))
            }
            Section("報名項目") {
                ForEach(registration.items) { item in
                    LabeledContent(item.name, value: AppFormatters.money(item.unitPrice))
                }
                LabeledContent("總金額", value: AppFormatters.money(registration.totalAmount)).bold()
            }
            Section("聯絡資料") {
                LabeledContent("聯絡電話", value: registration.contactPhone)
                LabeledContent("緊急聯絡人", value: registration.emergencyContactName ?? "—")
                LabeledContent("緊急聯絡電話", value: registration.emergencyContactPhone ?? "—")
                LabeledContent("備註", value: registration.notes ?? "—")
            }
            if let errorMessage { Section { Text(errorMessage).foregroundStyle(.red).font(.footnote) } }
            if registration.status == "registered" {
                Section {
                    Button("取消報名", role: .destructive) { showCancelConfirmation = true }
                        .frame(maxWidth: .infinity)
                }
            }
        }
        .navigationTitle("報名詳細資料")
        .navigationBarTitleDisplayMode(.inline)
        .loading(isLoading)
        .confirmationDialog("確定要取消這筆報名嗎？", isPresented: $showCancelConfirmation, titleVisibility: .visible) {
            Button("確定取消", role: .destructive) { Task { await cancel() } }
            Button("保留報名", role: .cancel) {}
        } message: { Text("取消後目前無法重新報名同一賽事。") }
    }

    private func cancel() async {
        guard let token = session.token else { return }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            try await APIClient.shared.cancelRegistration(token: token, id: registration.id)
            await onChanged()
            dismiss()
        } catch {
            session.handleUnauthorized(error)
            errorMessage = error.localizedDescription
        }
    }
}
