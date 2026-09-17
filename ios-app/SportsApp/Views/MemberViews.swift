import SwiftUI

struct MemberHomeView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var showAuthentication = false

    var body: some View {
        Group {
            if let member = session.member {
                List {
                    Section {
                        VStack(alignment: .leading, spacing: 5) {
                            Text(member.name).font(.title2.bold())
                            Text(member.email).foregroundStyle(.secondary)
                        }
                        .padding(.vertical, 6)
                    }
                    Section {
                        NavigationLink { ProfileEditView(member: member) } label: {
                            Label("修改基本資料", systemImage: "person.text.rectangle")
                        }
                        NavigationLink { RegistrationListView() } label: {
                            Label("我的報名紀錄", systemImage: "list.clipboard")
                        }
                    }
                    Section {
                        Button(role: .destructive) { Task { await session.logout() } } label: {
                            Label("登出", systemImage: "rectangle.portrait.and.arrow.right")
                        }
                    }
                }
            } else {
                VStack(spacing: 14) {
                    Image(systemName: "person.crop.circle.badge.questionmark").font(.largeTitle).foregroundStyle(.secondary)
                    Text("尚未登入").font(.title2.bold())
                    Text("登入後可報名賽事、修改基本資料及查看報名紀錄。")
                        .foregroundStyle(.secondary).multilineTextAlignment(.center)
                    Button("登入或註冊") { showAuthentication = true }.buttonStyle(.borderedProminent)
                }
                .padding()
            }
        }
        .navigationTitle("會員中心")
        .sheet(isPresented: $showAuthentication) { NavigationStack { AuthenticationView() } }
    }
}

struct ProfileEditView: View {
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var name: String
    @State private var email: String
    @State private var phone: String
    @State private var address: String
    @State private var gender: String
    @State private var hasBirthDate: Bool
    @State private var birthDate: Date
    @State private var isLoading = false
    @State private var errorMessage: String?

    init(member: Member) {
        _name = State(initialValue: member.name)
        _email = State(initialValue: member.email)
        _phone = State(initialValue: member.phone ?? "")
        _address = State(initialValue: member.address ?? "")
        _gender = State(initialValue: member.gender ?? "undisclosed")
        let parsedDate = member.birthDate.flatMap { value -> Date? in
            let formatter = DateFormatter()
            formatter.locale = Locale(identifier: "en_US_POSIX")
            formatter.dateFormat = "yyyy-MM-dd"
            return formatter.date(from: value)
        }
        _hasBirthDate = State(initialValue: parsedDate != nil)
        _birthDate = State(initialValue: parsedDate ?? Calendar.current.date(byAdding: .year, value: -18, to: .now)!)
    }

    var body: some View {
        Form {
            Section("基本資料") {
                TextField("姓名", text: $name).textContentType(.name)
                TextField("Email", text: $email).textContentType(.emailAddress).keyboardType(.emailAddress)
                TextField("電話", text: $phone).textContentType(.telephoneNumber).keyboardType(.phonePad)
                TextField("地址", text: $address).textContentType(.fullStreetAddress)
            }
            Section("性別與生日") {
                Picker("性別", selection: $gender) {
                    Text("男性").tag("male")
                    Text("女性").tag("female")
                    Text("其他").tag("other")
                    Text("不透露").tag("undisclosed")
                }
                Toggle("填寫生日", isOn: $hasBirthDate)
                if hasBirthDate {
                    DatePicker("生日", selection: $birthDate, in: ...Date.now, displayedComponents: .date)
                }
            }
            if let errorMessage { Section { Text(errorMessage).foregroundStyle(.red).font(.footnote) } }
        }
        .navigationTitle("修改基本資料")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar { ToolbarItem(placement: .confirmationAction) { Button("儲存") { Task { await save() } }.disabled(isLoading) } }
        .loading(isLoading)
    }

    private func save() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        do {
            try await session.updateMember(MemberUpdateRequest(
                name: name, email: email, phone: phone.nilIfBlank,
                birthDate: hasBirthDate ? formatter.string(from: birthDate) : nil,
                gender: gender, address: address.nilIfBlank
            ))
            dismiss()
        } catch {
            session.handleUnauthorized(error)
            errorMessage = error.localizedDescription
        }
    }
}
